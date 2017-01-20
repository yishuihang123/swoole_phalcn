<?php

namespace App\Core;

/**
 * 框架核心类 
 * 
 * */
class Core {

    // 响应实例
    public static $_response = null;
    // 请求实例
    public static $_request  = null;
    // 请求get参数
    public static $_get;
    // 请求post参数
    public static $_post;
    // 请求header参数
    public static $_header;
    // 请求server参数
    public static $_server;

}
