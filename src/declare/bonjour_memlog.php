<?php


/**
 * 开启缓存日志
 * 内部会自动清空，标记开启状态
 */
function bonjour_memlog_begin(){}

/**
 * 追加内容
 * @param mixed $content
 */
function bonjour_memlog_append($content){}

/**
 * 清空缓存
 */
function bonjour_memlog_clean(){}

/**
 * 获取当前日志并清空缓存
 * @return array
 */
function bonjour_memlog_get_clean(){}