/*
  +----------------------------------------------------------------------+
  | Swoole                                                               |
  +----------------------------------------------------------------------+
  | This source file is subject to version 2.0 of the Apache license,    |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.apache.org/licenses/LICENSE-2.0.html                      |
  | If you did not receive a copy of the Apache2.0 license and are unable|
  | to obtain it through the world-wide-web, please send a note to       |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: xinhua.guo  <woshiguo35@sina.com>                        |
  +----------------------------------------------------------------------+
 */

#include <string>
#include <iostream>


#include "PHP_API.hpp"
//#include "sha1.h"  



#define swoole_mysql_proxy_name  "swoole"

using namespace std;
using namespace PHP;

extern "C"
{
#include "php_swoole.h"
#include "module.h"
#include "ext/standard/php_http.h"
#include "swoole_mysql.h"
    int swModule_init(swModule *);
    void responseAuth(Object &_this, Args &args, Variant &retval);
}

int mysql_proxy_get_length(swProtocol *protocol, swConnection *conn, char *data, uint32_t length);

void sendConnectOk(Object &_this, Args &args, Variant &retval);
void getDbName(Object &_this, Args &args, Variant &retval);
void sendConnectAuth(Object &_this, Args &args, Variant &retval);
void getConnResult(Object &_this, Args &args, Variant &retval);
void getSql(Object &_this, Args &args, Variant &retval);
void packOkData(Object &_this, Args &args, Variant &retval);
void packErrorData(Object &_this, Args &args, Variant &retval);
void packResultData(Object &_this, Args &args, Variant &retval);

int swModule_init(swModule *module)
{

    module->name = (char *) "mysql_proxy";
    swModule_register_global_function((char *) "mysql_proxy_get_length", (void *) mysql_proxy_get_length);

    Class c("MysqlProtocol");
    /**
     * 发送链接ok包
     */
    c.addMethod("sendConnectOk", sendConnectOk);
    /**
     * 获取dbname
     */
    c.addMethod("getDbName", getDbName);
    /**
     * 发送auth包
     */
    c.addMethod("sendConnectAuth", sendConnectAuth);

    /**
     * 相应mysql server的Auth的包
     */
    c.addMethod("responseAuth", responseAuth);


    /**
     * 获取链接的最后一个包
     */
    c.addMethod("getConnResult", getConnResult);


    /**
     * 获取sql
     */
    c.addMethod("getSql", getSql);
    /**
     * 发送ok包 insert update
     */
    c.addMethod("packOkData", packOkData);
    /**
     * 发送error包
     */
    c.addMethod("packErrorData", packErrorData);
    /**
     * 发送数组  selelct
     */
    c.addMethod("packResultData", packResultData);

    /**
     * 激活类
     */
    c.activate();

    return SW_OK;
}

static sw_inline void mysql_pack_2length(int length, char *buf)
{
    buf[1] = length >> 8;
    buf[0] = length;
}

static sw_inline void mysql_pack_4length(int length, char *buf)
{
    buf[3] = length >> 24;
    buf[2] = length >> 16;
    buf[1] = length >> 8;
    buf[0] = length;
}

static sw_inline void mysql_pack_8length(uint64_t length, char *buf)
{
    buf[7] = length >> 56;
    buf[6] = length >> 48;
    buf[5] = length >> 40; //?????
    buf[4] = length >> 32;
    buf[3] = length >> 24;
    buf[2] = length >> 16;
    buf[1] = length >> 8;
    buf[0] = length;
}

static sw_inline void swString_check_size(swString *str, int s_len)
{
    int new_size = str->length + s_len;
    if (new_size > str->size)
    {
        if (swString_extend(str, swoole_size_align(new_size * 2, sysconf(_SC_PAGESIZE))) < 0)
        {
            swoole_error_log(SW_LOG_ERROR, SW_ERROR_MALLOC_FAIL, "malloc[0] failed.");
        }
    }
}

static sw_inline void skip_one_type(swString * buf)
{
    swString_check_size(buf, 1);
    buf->str[buf->length] = 0;
    buf->length++;
}

static sw_inline void encode_mysql_integer(swString *buffer, uint64_t num)
{
    if (num == 0)
    {
        buffer->str[buffer->length] = 251;
        buffer->length++;
    } else if (1 <= num && num <= 250)
    {//column num in result type packet
        buffer->str[buffer->length] = num;
        buffer->length++;
    } else if (num <= 0xff)//2byte
    {
        buffer->str[buffer->length] = 252;
        buffer->length++;
        mysql_pack_2length((int) num, buffer->str + buffer->length);
        buffer->length += 2;
    } else if (num <= 0xfff)//3byte
    {
        buffer->str[buffer->length] = 253;
        buffer->length++;
        mysql_pack_length((int) num, buffer->str + buffer->length);
        buffer->length += 3;
    } else
    {
        buffer->str[buffer->length] = 254;
        buffer->length++;
        mysql_pack_8length(num, buffer->str + buffer->length);
        buffer->length += 8;
    }
}

static sw_inline void pack_mysql_eof(swString *buf, zend_uchar *pack_num)
{
    //EOF, length (3byte) + id(1byte) + 0xFE + warning(2byte) + status(2byte)
    // swString_check_size(sql_data_buffer, 5);
    mysql_pack_length(5, buf->str + buf->length);
    buf->length += 3;

    buf->str[buf->length] = ++(*pack_num);
    buf->length++;

    buf->str[buf->length] = 0xFE;
    buf->length++;

    *(short *) (buf->str + buf->length) = 0;
    buf->length += 2;

    *(short *) (buf->str + buf->length) = 0;
    buf->length += 2;
}

void sendConnectOk(Object &_this, Args &args, Variant &retval)
{
    char responseOk[11] = {0};
    responseOk[3] = 2; //number 2
    mysql_pack_length(7, responseOk); //length 不包含包头的长度

    zval *obj = args[0].ptr();
    int fd = args[1].toInt();
    swServer *serv = (swServer*) swoole_get_object(obj);
    int flag = serv->send(serv, fd, &responseOk, sizeof (responseOk));
    if (flag)
    {
        retval = 1;
        //        return Variant(1);
    } else
    {
        retval = 0;
        //        return Variant(0);
    }
}

void getDbName(Object &_this, Args &args, Variant &retval)
{
    char *data = args[0].toCString();
    char username[200] = {0};
    char database[200] = {0};
    char pwd[200] = {0};
    strcpy(username, data + 36);
    strcpy(pwd, data + 36 + strlen(username) + 1);
    if (strlen(pwd) == 0)
    {
         strcpy(database, data + 36 + strlen(username) + strlen(pwd) + 2);
    } else
    {
        strcpy(database, data + 36 + strlen(username) + 22);
    }
    retval = database;
    //    SW_RETURN_STRING(database, 1);
}

void sendConnectAuth(Object &_this, Args &args, Variant &retval)
{
    /*
     *1              [0a] protocol version
    string[NUL]    server version
    4              connection id
    string[8]      auth-plugin-data-part-1
    1              [00] filler
    2              capability flags (lower 2 bytes)
      if more data in the packet:
    1              character set
    2              status flags
    2              capability flags (upper 2 bytes)
      if capabilities & CLIENT_PLUGIN_AUTH {
    1              length of auth-plugin-data
      } else {
    1              [00]
      }
    string[10]     reserved (all [00])
      if capabilities & CLIENT_SECURE_CONNECTION {
    string[$len]   auth-plugin-data-part-2 ($len=MAX(13, length of auth-plugin-data - 8))
      if capabilities & CLIENT_PLUGIN_AUTH {
    string[NUL]    auth-plugin name
      }
     */
#pragma pack (1)

    typedef struct
    {
        char packet_length[3];
        uint8_t packet_number; //header

        uint8_t protocol_version;
        char proxy_name[sizeof (swoole_mysql_proxy_name)];
        int connection_id;
        uint64_t random;
        uint8_t reserved1;
        uint16_t capability_flags;
        char character_set;
        int8_t random_len;
        char reserved2[10];
        char random2[13];
    } swoole_mysql_handshake_response;

#pragma pack ()

    int fd = args[1].toInt();

    swoole_mysql_handshake_response response = {0};
    int packet_length = sizeof (swoole_mysql_handshake_response) - 4;
    //length
    mysql_pack_length(packet_length, response.packet_length);
    response.protocol_version = 10;
    memcpy(response.proxy_name, swoole_mysql_proxy_name, sizeof (swoole_mysql_proxy_name));
    response.connection_id = fd;
    response.character_set = 8;

    zval *obj = args[0].ptr();
    swServer *serv = (swServer*) swoole_get_object(obj);
    int flag = serv->send(serv, fd, &response, sizeof (response));
    if (flag)
    {
        retval = 1;
    } else
    {
        retval = 0;
    }

    //return Variant((char *)&response, sizeof (response));

}

void getConnResult(Object &_this, Args &args, Variant &retval)
{
    string str = args[0].toString();

    const char *buf = str.data();
    char *tmp = (char*) buf;
    int packet_length = mysql_uint3korr(tmp);
    //int packet_number = tmp[3];
    tmp += 4;

    uint8_t opcode = *tmp;
    tmp += 1;

    //ERROR Packet
    if (opcode == 0xff)
    {
        string tmp_str = string(tmp + 2, packet_length - 3);
        retval = tmp_str;
    } else
    {
        retval = 1;
    }
}

void getSql(Object &_this, Args &args, Variant &retval)
{
    string str = args[0].toString();

    const char *buf = str.data();
    // int packet_length = mysql_uint3korr(buf);
    //command
    int command = buf[4];
    //    if (command != SW_MYSQL_COM_QUERY)
    //    {
    //        return Variant(0);
    //    }

    Array map(retval);
    map.set("cmd", (long) command);
    map.set("sql", buf + 5);
    // return Variant(buf + 5, packet_length - 1);
}

#define UTF8_MB4 "utf8mb4"
#define UTF8_MB3 "utf8"

typedef struct _mysql_charset
{
    unsigned int nr;
    const char *name;
    const char *collation;
} mysql_charset;

static const mysql_charset swoole_mysql_charsets[] = {
    { 1, "big5", "big5_chinese_ci"},
    { 3, "dec8", "dec8_swedish_ci"},
    { 4, "cp850", "cp850_general_ci"},
    { 6, "hp8", "hp8_english_ci"},
    { 7, "koi8r", "koi8r_general_ci"},
    { 8, "latin1", "latin1_swedish_ci"},
    { 5, "latin1", "latin1_german1_ci"},
    { 9, "latin2", "latin2_general_ci"},
    { 2, "latin2", "latin2_czech_cs"},
    { 10, "swe7", "swe7_swedish_ci"},
    { 11, "ascii", "ascii_general_ci"},
    { 12, "ujis", "ujis_japanese_ci"},
    { 13, "sjis", "sjis_japanese_ci"},
    { 16, "hebrew", "hebrew_general_ci"},
    { 17, "filename", "filename"},
    { 18, "tis620", "tis620_thai_ci"},
    { 19, "euckr", "euckr_korean_ci"},
    { 21, "latin2", "latin2_hungarian_ci"},
    { 27, "latin2", "latin2_croatian_ci"},
    { 22, "koi8u", "koi8u_general_ci"},
    { 24, "gb2312", "gb2312_chinese_ci"},
    { 25, "greek", "greek_general_ci"},
    { 26, "cp1250", "cp1250_general_ci"},
    { 28, "gbk", "gbk_chinese_ci"},
    { 30, "latin5", "latin5_turkish_ci"},
    { 31, "latin1", "latin1_german2_ci"},
    { 15, "latin1", "latin1_danish_ci"},
    { 32, "armscii8", "armscii8_general_ci"},
    { 33, UTF8_MB3, UTF8_MB3"_general_ci"},
    { 35, "ucs2", "ucs2_general_ci"},
    { 36, "cp866", "cp866_general_ci"},
    { 37, "keybcs2", "keybcs2_general_ci"},
    { 38, "macce", "macce_general_ci"},
    { 39, "macroman", "macroman_general_ci"},
    { 40, "cp852", "cp852_general_ci"},
    { 41, "latin7", "latin7_general_ci"},
    { 20, "latin7", "latin7_estonian_cs"},
    { 57, "cp1256", "cp1256_general_ci"},
    { 59, "cp1257", "cp1257_general_ci"},
    { 63, "binary", "binary"},
    { 97, "eucjpms", "eucjpms_japanese_ci"},
    { 29, "cp1257", "cp1257_lithuanian_ci"},
    { 31, "latin1", "latin1_german2_ci"},
    { 34, "cp1250", "cp1250_czech_cs"},
    { 42, "latin7", "latin7_general_cs"},
    { 43, "macce", "macce_bin"},
    { 44, "cp1250", "cp1250_croatian_ci"},
    { 45, UTF8_MB4, UTF8_MB4"_general_ci"},
    { 46, UTF8_MB4, UTF8_MB4"_bin"},
    { 47, "latin1", "latin1_bin"},
    { 48, "latin1", "latin1_general_ci"},
    { 49, "latin1", "latin1_general_cs"},
    { 51, "cp1251", "cp1251_general_ci"},
    { 14, "cp1251", "cp1251_bulgarian_ci"},
    { 23, "cp1251", "cp1251_ukrainian_ci"},
    { 50, "cp1251", "cp1251_bin"},
    { 52, "cp1251", "cp1251_general_cs"},
    { 53, "macroman", "macroman_bin"},
    { 54, "utf16", "utf16_general_ci"},
    { 55, "utf16", "utf16_bin"},
    { 56, "utf16le", "utf16le_general_ci"},
    { 58, "cp1257", "cp1257_bin"},
    { 60, "utf32", "utf32_general_ci"},
    { 61, "utf32", "utf32_bin"},
    { 62, "utf16le", "utf16le_bin"},
    { 64, "armscii8", "armscii8_bin"},
    { 65, "ascii", "ascii_bin"},
    { 66, "cp1250", "cp1250_bin"},
    { 67, "cp1256", "cp1256_bin"},
    { 68, "cp866", "cp866_bin"},
    { 69, "dec8", "dec8_bin"},
    { 70, "greek", "greek_bin"},
    { 71, "hebrew", "hebrew_bin"},
    { 72, "hp8", "hp8_bin"},
    { 73, "keybcs2", "keybcs2_bin"},
    { 74, "koi8r", "koi8r_bin"},
    { 75, "koi8u", "koi8u_bin"},
    { 77, "latin2", "latin2_bin"},
    { 78, "latin5", "latin5_bin"},
    { 79, "latin7", "latin7_bin"},
    { 80, "cp850", "cp850_bin"},
    { 81, "cp852", "cp852_bin"},
    { 82, "swe7", "swe7_bin"},
    { 83, UTF8_MB3, UTF8_MB3"_bin"},
    { 84, "big5", "big5_bin"},
    { 85, "euckr", "euckr_bin"},
    { 86, "gb2312", "gb2312_bin"},
    { 87, "gbk", "gbk_bin"},
    { 88, "sjis", "sjis_bin"},
    { 89, "tis620", "tis620_bin"},
    { 90, "ucs2", "ucs2_bin"},
    { 91, "ujis", "ujis_bin"},
    { 92, "geostd8", "geostd8_general_ci"},
    { 93, "geostd8", "geostd8_bin"},
    { 94, "latin1", "latin1_spanish_ci"},
    { 95, "cp932", "cp932_japanese_ci"},
    { 96, "cp932", "cp932_bin"},
    { 97, "eucjpms", "eucjpms_japanese_ci"},
    { 98, "eucjpms", "eucjpms_bin"},
    { 99, "cp1250", "cp1250_polish_ci"},
    { 128, "ucs2", "ucs2_unicode_ci"},
    { 129, "ucs2", "ucs2_icelandic_ci"},
    { 130, "ucs2", "ucs2_latvian_ci"},
    { 131, "ucs2", "ucs2_romanian_ci"},
    { 132, "ucs2", "ucs2_slovenian_ci"},
    { 133, "ucs2", "ucs2_polish_ci"},
    { 134, "ucs2", "ucs2_estonian_ci"},
    { 135, "ucs2", "ucs2_spanish_ci"},
    { 136, "ucs2", "ucs2_swedish_ci"},
    { 137, "ucs2", "ucs2_turkish_ci"},
    { 138, "ucs2", "ucs2_czech_ci"},
    { 139, "ucs2", "ucs2_danish_ci"},
    { 140, "ucs2", "ucs2_lithuanian_ci"},
    { 141, "ucs2", "ucs2_slovak_ci"},
    { 142, "ucs2", "ucs2_spanish2_ci"},
    { 143, "ucs2", "ucs2_roman_ci"},
    { 144, "ucs2", "ucs2_persian_ci"},
    { 145, "ucs2", "ucs2_esperanto_ci"},
    { 146, "ucs2", "ucs2_hungarian_ci"},
    { 147, "ucs2", "ucs2_sinhala_ci"},
    { 148, "ucs2", "ucs2_german2_ci"},
    { 149, "ucs2", "ucs2_croatian_ci"},
    { 150, "ucs2", "ucs2_unicode_520_ci"},
    { 151, "ucs2", "ucs2_vietnamese_ci"},
    { 160, "utf32", "utf32_unicode_ci"},
    { 161, "utf32", "utf32_icelandic_ci"},
    { 162, "utf32", "utf32_latvian_ci"},
    { 163, "utf32", "utf32_romanian_ci"},
    { 164, "utf32", "utf32_slovenian_ci"},
    { 165, "utf32", "utf32_polish_ci"},
    { 166, "utf32", "utf32_estonian_ci"},
    { 167, "utf32", "utf32_spanish_ci"},
    { 168, "utf32", "utf32_swedish_ci"},
    { 169, "utf32", "utf32_turkish_ci"},
    { 170, "utf32", "utf32_czech_ci"},
    { 171, "utf32", "utf32_danish_ci"},
    { 172, "utf32", "utf32_lithuanian_ci"},
    { 173, "utf32", "utf32_slovak_ci"},
    { 174, "utf32", "utf32_spanish2_ci"},
    { 175, "utf32", "utf32_roman_ci"},
    { 176, "utf32", "utf32_persian_ci"},
    { 177, "utf32", "utf32_esperanto_ci"},
    { 178, "utf32", "utf32_hungarian_ci"},
    { 179, "utf32", "utf32_sinhala_ci"},
    { 180, "utf32", "utf32_german2_ci"},
    { 181, "utf32", "utf32_croatian_ci"},
    { 182, "utf32", "utf32_unicode_520_ci"},
    { 183, "utf32", "utf32_vietnamese_ci"},
    { 192, UTF8_MB3, UTF8_MB3"_unicode_ci"},
    { 193, UTF8_MB3, UTF8_MB3"_icelandic_ci"},
    { 194, UTF8_MB3, UTF8_MB3"_latvian_ci"},
    { 195, UTF8_MB3, UTF8_MB3"_romanian_ci"},
    { 196, UTF8_MB3, UTF8_MB3"_slovenian_ci"},
    { 197, UTF8_MB3, UTF8_MB3"_polish_ci"},
    { 198, UTF8_MB3, UTF8_MB3"_estonian_ci"},
    { 199, UTF8_MB3, UTF8_MB3"_spanish_ci"},
    { 200, UTF8_MB3, UTF8_MB3"_swedish_ci"},
    { 201, UTF8_MB3, UTF8_MB3"_turkish_ci"},
    { 202, UTF8_MB3, UTF8_MB3"_czech_ci"},
    { 203, UTF8_MB3, UTF8_MB3"_danish_ci"},
    { 204, UTF8_MB3, UTF8_MB3"_lithuanian_ci"},
    { 205, UTF8_MB3, UTF8_MB3"_slovak_ci"},
    { 206, UTF8_MB3, UTF8_MB3"_spanish2_ci"},
    { 207, UTF8_MB3, UTF8_MB3"_roman_ci"},
    { 208, UTF8_MB3, UTF8_MB3"_persian_ci"},
    { 209, UTF8_MB3, UTF8_MB3"_esperanto_ci"},
    { 210, UTF8_MB3, UTF8_MB3"_hungarian_ci"},
    { 211, UTF8_MB3, UTF8_MB3"_sinhala_ci"},
    { 212, UTF8_MB3, UTF8_MB3"_german2_ci"},
    { 213, UTF8_MB3, UTF8_MB3"_croatian_ci"},
    { 214, UTF8_MB3, UTF8_MB3"_unicode_520_ci"},
    { 215, UTF8_MB3, UTF8_MB3"_vietnamese_ci"},

    { 224, UTF8_MB4, UTF8_MB4"_unicode_ci"},
    { 225, UTF8_MB4, UTF8_MB4"_icelandic_ci"},
    { 226, UTF8_MB4, UTF8_MB4"_latvian_ci"},
    { 227, UTF8_MB4, UTF8_MB4"_romanian_ci"},
    { 228, UTF8_MB4, UTF8_MB4"_slovenian_ci"},
    { 229, UTF8_MB4, UTF8_MB4"_polish_ci"},
    { 230, UTF8_MB4, UTF8_MB4"_estonian_ci"},
    { 231, UTF8_MB4, UTF8_MB4"_spanish_ci"},
    { 232, UTF8_MB4, UTF8_MB4"_swedish_ci"},
    { 233, UTF8_MB4, UTF8_MB4"_turkish_ci"},
    { 234, UTF8_MB4, UTF8_MB4"_czech_ci"},
    { 235, UTF8_MB4, UTF8_MB4"_danish_ci"},
    { 236, UTF8_MB4, UTF8_MB4"_lithuanian_ci"},
    { 237, UTF8_MB4, UTF8_MB4"_slovak_ci"},
    { 238, UTF8_MB4, UTF8_MB4"_spanish2_ci"},
    { 239, UTF8_MB4, UTF8_MB4"_roman_ci"},
    { 240, UTF8_MB4, UTF8_MB4"_persian_ci"},
    { 241, UTF8_MB4, UTF8_MB4"_esperanto_ci"},
    { 242, UTF8_MB4, UTF8_MB4"_hungarian_ci"},
    { 243, UTF8_MB4, UTF8_MB4"_sinhala_ci"},
    { 244, UTF8_MB4, UTF8_MB4"_german2_ci"},
    { 245, UTF8_MB4, UTF8_MB4"_croatian_ci"},
    { 246, UTF8_MB4, UTF8_MB4"_unicode_520_ci"},
    { 247, UTF8_MB4, UTF8_MB4"_vietnamese_ci"},
    { 248, "gb18030", "gb18030_chinese_ci"},
    { 249, "gb18030", "gb18030_bin"},
    { 254, UTF8_MB3, UTF8_MB3"_general_cs"},
    { 0, NULL, NULL},
};

static int mysql_get_charset(const char *name)
{
    const mysql_charset *c = swoole_mysql_charsets;
    while (c[0].nr != 0)
    {
        if (!strcasecmp(c->name, name))
        {
            return c->nr;
        }
        ++c;
    }
    return -1;
}

void responseAuth(Object &_this, Args &args, Variant &retval)
{
    string recv_str = args[0].toString();
    string db_str = args[1].toString();
    string user_str = args[2].toString();
    string password_str = args[3].toString();
    string charset_str = args[4].toString();

    const char *buf = recv_str.data();
    int len = recv_str.length();

    mysql_connector connector = {0};

    char *tmp = (char *) buf;

    /**
     * handshake request
     */
    mysql_handshake_request request;
    bzero(&request, sizeof (request));

    request.packet_length = mysql_uint3korr(tmp);
    //continue to wait for data
    if (len < request.packet_length + 4)
    {//todo
        return;
    }

    request.packet_number = tmp[3];
    tmp += 4;

    request.protocol_version = *tmp;
    tmp += 1;

    //ERROR Packet
    if (request.protocol_version == 0xff)
    {//todo
        //        connector->error_code = *(uint16_t *) tmp;
        //        connector->error_msg = tmp + 2;
        //        connector->error_length = request.packet_length - 3;
        Array map(retval);
        map.set("error_msg", tmp + 2);
        map.set("error_code", *(uint16_t *) tmp);
        return;
    }

    //1              [0a] protocol version
    request.server_version = tmp;
    tmp += (strlen(request.server_version) + 1);
    //4              connection id
    request.connection_id = *((int *) tmp);
    tmp += 4;
    //string[8]      auth-plugin-data-part-1
    memcpy(request.auth_plugin_data, tmp, 8);
    tmp += 8;
    //1              [00] filler
    request.filler = *tmp;
    tmp += 1;
    //2              capability flags (lower 2 bytes)
    memcpy(((char *) (&request.capability_flags)), tmp, 2);
    tmp += 2;

    if (tmp - tmp < len)
    {
        //1              character set
        request.character_set = *tmp;
        tmp += 1;
        //2              status flags
        memcpy(&request.status_flags, tmp, 2);
        tmp += 2;
        //2              capability flags (upper 2 bytes)
        memcpy(((char *) (&request.capability_flags) + 2), tmp, 2);
        tmp += 2;

        request.l_auth_plugin_data = *tmp;
        tmp += 1;

        memcpy(&request.reserved, tmp, sizeof (request.reserved));
        tmp += sizeof (request.reserved);

        if (request.capability_flags & SW_MYSQL_CLIENT_SECURE_CONNECTION)
        {
            int len = MAX(13, request.l_auth_plugin_data - 8);
            memcpy(request.auth_plugin_data + 8, tmp, len);
            tmp += len;
        }

        if (request.capability_flags & SW_MYSQL_CLIENT_PLUGIN_AUTH)
        {
            request.auth_plugin_name = tmp;
            request.l_auth_plugin_name = MIN(strlen(tmp), len - (tmp - buf));
        }
    }

    int value;
    tmp = connector.buf + 4;
    //capability flags, CLIENT_PROTOCOL_41 always set
    value = SW_MYSQL_CLIENT_PROTOCOL_41 | SW_MYSQL_CLIENT_SECURE_CONNECTION | SW_MYSQL_CLIENT_CONNECT_WITH_DB | SW_MYSQL_CLIENT_PLUGIN_AUTH;
    memcpy(tmp, &value, sizeof (value));
    tmp += 4;
    //max-packet size
    value = 300;
    memcpy(tmp, &value, sizeof (value));
    tmp += 4;

    connector.character_set = mysql_get_charset(charset_str.data());
    if (connector.character_set < 0)
    {
        Array map(retval);
        map.set("error_msg", "unkown charset");
        map.set("error_code", 10000);
        return;
    }

    //use the server character_set when the character_set is not set.
    if (connector.character_set == 0)
    {
        connector.character_set = request.character_set;
    }
    //character set
    *tmp = connector.character_set;
    tmp += 1;

    //string[23]     reserved (all [0])
    tmp += 23;

    //string[NUL]    username
    memcpy(tmp, user_str.data(), user_str.length());
    tmp[user_str.length()] = '\0';
    tmp += (user_str.length() + 1);

    if (password_str.length() > 0)
    {
        //auth-response
        char hash_0[20];
        bzero(hash_0, sizeof (hash_0));
        php_swoole_sha1(password_str.data(), (int) password_str.length(), (uchar *) hash_0);
        char hash_1[20];
        bzero(hash_1, sizeof (hash_1));
        php_swoole_sha1(hash_0, sizeof (hash_0), (uchar *) hash_1);

        char str[40];
        memcpy(str, request.auth_plugin_data, 20);
        memcpy(str + 20, hash_1, 20);

        char hash_2[20];
        php_swoole_sha1(str, sizeof (str), (uchar *) hash_2);

        char hash_3[20];

        int *a = (int *) hash_2;
        int *b = (int *) hash_0;
        int *c = (int *) hash_3;

        int i;
        for (i = 0; i < 5; i++)
        {
            c[i] = a[i] ^ b[i];
        }

        *tmp = 20;
        memcpy(tmp + 1, hash_3, 20);
        tmp += 21;
    } else
    {
        *tmp = 0;
        tmp++;
    }

    //string[NUL]    database
    memcpy(tmp, db_str.data(), db_str.length());
    tmp[db_str.length()] = '\0';
    tmp += (db_str.length() + 1);

    //string[NUL]    auth plugin name
    memcpy(tmp, request.auth_plugin_name, request.l_auth_plugin_name);
    tmp[request.l_auth_plugin_name] = '\0';
    tmp += (request.l_auth_plugin_name + 1);

    connector.packet_length = tmp - connector.buf - 4;
    mysql_pack_length(connector.packet_length, connector.buf);
    connector.buf[3] = 1;
    string tmp_str = string(connector.buf, connector.packet_length + 4);
    retval = tmp_str;
}

void packOkData(Object &_this, Args &args, Variant &retval)
{
    Variant effect_rows = args[0];
    Variant insert_id = args[1];

    swString *sql_data_buffer = swString_new(SW_BUFFER_SIZE_STD);
    if (!sql_data_buffer)
    {
        swoole_error_log(SW_LOG_ERROR, SW_ERROR_MALLOC_FAIL, "malloc[0] failed.");
        retval = 0;
    }

    sql_data_buffer->str[3] = 1; //number
    int start = sql_data_buffer->length = 4;
    //ok packet(insert update)
    sql_data_buffer->str[sql_data_buffer->length] = 0;
    sql_data_buffer->length++;

    /*effect rows*/
    encode_mysql_integer(sql_data_buffer, effect_rows.toInt());

    /* insert id */
    encode_mysql_integer(sql_data_buffer, insert_id.toInt());

    /* skip server status */
    sql_data_buffer->str[sql_data_buffer->length] = 0;
    sql_data_buffer->str[sql_data_buffer->length + 1] = 0;
    sql_data_buffer->length += 2;

    /*skip server warnings */
    sql_data_buffer->str[sql_data_buffer->length] = 0;
    sql_data_buffer->str[sql_data_buffer->length + 1] = 0;
    sql_data_buffer->length += 2;

    int total_len = sql_data_buffer->length - start;
    mysql_pack_length(total_len, sql_data_buffer->str);

    string tmp_str = string(sql_data_buffer->str, sql_data_buffer->length);
    retval = tmp_str;
    swString_free(sql_data_buffer);

}

void packErrorData(Object &_this, Args &args, Variant &retval)
{

    Variant error_code = args[0];
    Variant error_msg = args[1];
    swString *sql_data_buffer = swString_new(SW_BUFFER_SIZE_STD);
    if (!sql_data_buffer)
    {
        swoole_error_log(SW_LOG_ERROR, SW_ERROR_MALLOC_FAIL, "malloc[0] failed.");
        retval = 0;
        return;
    }

    sql_data_buffer->str[3] = 2; //number
    sql_data_buffer->str[4] = 0xFF; //ok packet(insert update)
    sql_data_buffer->length = 5;

    mysql_pack_2length((int) error_code.toInt(), sql_data_buffer->str + sql_data_buffer->length + 1);
    sql_data_buffer->length += 2;

    sql_data_buffer->str[sql_data_buffer->length] = '#';
    sql_data_buffer->length += 1;

    /* skip server status */
    sql_data_buffer->str[sql_data_buffer->length] = 0;
    sql_data_buffer->str[sql_data_buffer->length + 1] = 0;
    sql_data_buffer->str[sql_data_buffer->length + 2] = 0;
    sql_data_buffer->str[sql_data_buffer->length + 3] = 0;
    sql_data_buffer->str[sql_data_buffer->length + 4] = 0;
    sql_data_buffer->length += 5;

    /*error msg */
    //msg_len++; //for '\0'                                                                                                                                                                                                  
    memcpy(sql_data_buffer->str + sql_data_buffer->length, error_msg.toString().c_str(), error_msg.toString().length());
    sql_data_buffer->length += error_msg.toString().length();

    //pack the total len                                                                                                                                                                                                   
    mysql_pack_length(sql_data_buffer->length - 4, sql_data_buffer->str);


    string tmp_str = string(sql_data_buffer->str, sql_data_buffer->length);
    retval = tmp_str;
    swString_free(sql_data_buffer);

}

static sw_inline void encode_field_num(swString *buffer, uint64_t num)
{
    if (num == 0)
    {
        buffer->str[buffer->length - 1] = 251;
    } else if (1 <= num && num <= 250)
    {//column num in result type packet
        buffer->str[buffer->length - 1] = num;
    } else if (num <= 0xff)//2byte
    {
        buffer->str[buffer->length - 1] = 252;
        swString_check_size(buffer, 2);
        mysql_pack_2length((int) num, buffer->str + buffer->length);
        buffer->length += 2;
    } else if (num <= 0xfff)//3byte
    {
        buffer->str[buffer->length - 1] = 253;
        swString_check_size(buffer, 3);
        mysql_pack_length((int) num, buffer->str + buffer->length);
        buffer->length += 3;
    } else
    {
        buffer->str[buffer->length - 1] = 254;
        swString_check_size(buffer, 8);
        mysql_pack_8length(num, buffer->str + buffer->length);
        buffer->length += 8;
    }
}

void packResultData(Object &_this, Args &args, Variant &retval)
{
    Variant vars = args[0];
    Array arrays(vars);
    zend_uchar pack_num = 0; //0--255

    swString *sql_data_buffer = swString_new(SW_BUFFER_SIZE_STD);
    if (!sql_data_buffer)
    {
        swoole_error_log(SW_LOG_ERROR, SW_ERROR_MALLOC_FAIL, "malloc[0] failed.");
        retval = 0;
        return;
    }
    sql_data_buffer->str[3] = ++pack_num; //number
    //sql_data_buffer->str[4] = 1; //result type packet
    sql_data_buffer->length = 5;


    Variant first_row = arrays[0];
    Array first_row_arr(first_row);
    if (first_row_arr.count() <= 0)
    {
        swoole_error_log(SW_LOG_ERROR, SW_ERROR_PHP_FATAL_ERROR, "first row null");
        retval = 0;
        return;
    }

    /*column num*/
    int column_num = first_row_arr.count();
    encode_field_num(sql_data_buffer, column_num);

    const char *key;
    int keylen;
    for (auto i = first_row_arr.begin(); i != first_row_arr.end(); i++)
    {
        if (!i.key().isString())
        {
            swoole_error_log(SW_LOG_ERROR, SW_ERROR_PHP_FATAL_ERROR, "key must be string");
            retval = 0;
            return;
        }
        key = i.key().toString().c_str();
        keylen = i.key().toString().length();

        /*header space 4*/
        swString_check_size(sql_data_buffer, 4);
        sql_data_buffer->length += 4;
        int start = sql_data_buffer->length;

        /*catelog*/
        encode_mysql_integer(sql_data_buffer, 3);
        swString_check_size(sql_data_buffer, 3);
        memcpy(sql_data_buffer->str + sql_data_buffer->length, "def", 3);
        sql_data_buffer->length += 3;

        /*dbname*/
        skip_one_type(sql_data_buffer);


        /*table name*/
        skip_one_type(sql_data_buffer);

        /*origin_table name*/
        skip_one_type(sql_data_buffer);

        /*field name*/
        encode_mysql_integer(sql_data_buffer, keylen);
        swString_check_size(sql_data_buffer, keylen);
        memcpy(sql_data_buffer->str + sql_data_buffer->length, key, keylen);
        sql_data_buffer->length += keylen;

        /*origin_field name*/
        encode_mysql_integer(sql_data_buffer, keylen);
        swString_check_size(sql_data_buffer, keylen);
        memcpy(sql_data_buffer->str + sql_data_buffer->length, key, keylen);
        sql_data_buffer->length += keylen;


        /*fill 1 byte vale is 12 always*/
        sql_data_buffer->str[sql_data_buffer->length] = 12;
        sql_data_buffer->length++;

        /*charset*/
        swString_check_size(sql_data_buffer, 2);
        mysql_pack_2length(33, sql_data_buffer->str + sql_data_buffer->length); //???? just utf8?
        sql_data_buffer->length += 2;

        /*field length*/
        swString_check_size(sql_data_buffer, 4);
        mysql_pack_4length(keylen, sql_data_buffer->str + sql_data_buffer->length);
        sql_data_buffer->length += 4;

        /*field type*/
        swString_check_size(sql_data_buffer, 1);
        sql_data_buffer->str[sql_data_buffer->length] = SW_MYSQL_TYPE_STRING; //php array cannot get this flag, all of it is string!
        sql_data_buffer->length++;

        /*field flag*/
        swString_check_size(sql_data_buffer, 2);
        mysql_pack_2length(0, sql_data_buffer->str + sql_data_buffer->length); //php array cannot get this flag, all of it is string!
        sql_data_buffer->length += 2;

        /*for decimals precision 1*/
        skip_one_type(sql_data_buffer);

        /*fill 2*/
        skip_one_type(sql_data_buffer);
        skip_one_type(sql_data_buffer);

        /*set header*/
        int total_len = sql_data_buffer->length - start;
        mysql_pack_length(total_len, sql_data_buffer->str + start - 4);
        sql_data_buffer->str[start - 1] = ++pack_num; //pack number

    }

    pack_mysql_eof(sql_data_buffer, &pack_num);
    /*
     * for rows 
     */

    for (int i = 0; i < arrays.count(); i++)
    {
        Variant row = arrays[i];
        Array row_arr(row);

        /*header space 4*/
        swString_check_size(sql_data_buffer, 4);
        sql_data_buffer->length += 4;
        int start = sql_data_buffer->length;

        for (auto j = row_arr.begin(); j != row_arr.end(); j++)
        {
            const char *value = j.value().toString().c_str();
            int value_len = j.value().toString().length();
            /*encode value*/
            encode_mysql_integer(sql_data_buffer, value_len);
            swString_check_size(sql_data_buffer, value_len);
            memcpy(sql_data_buffer->str + sql_data_buffer->length, value, value_len);
            sql_data_buffer->length += value_len;
        }

        /*set header*/
        int total_len = sql_data_buffer->length - start;
        mysql_pack_length(total_len, sql_data_buffer->str + start - 4);
        sql_data_buffer->str[start - 1] = ++pack_num; //pack number
    }

    pack_mysql_eof(sql_data_buffer, &pack_num);

    //pack the total len
    mysql_pack_length(1, sql_data_buffer->str); //pdo is 1 ,???why


    string tmp_str = string(sql_data_buffer->str, sql_data_buffer->length);
    retval = tmp_str;

    swString_free(sql_data_buffer);

}

int mysql_proxy_get_length(swProtocol *protocol, swConnection *conn, char *data, uint32_t length)
{
    if (length < 4)
    {

        return SW_OK;
    }

    int len = mysql_uint3korr(data);
    return len + 4;

}
