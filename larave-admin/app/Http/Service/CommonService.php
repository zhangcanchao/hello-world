<?php

/**
 *  共同方法抽取
 */

namespace App\Http\Service;


class CommonService
{


    /**
     * 后台文件上传
     * @param $folderName 文件夹名称
     * @param $file
     * @return mixed
     */
    public function upload($folderName, $file)
    {
        $name = $file->getClientOriginalName();
        $name = str_replace(['\\', '/', ':', '*', '#', '?', '<', '>', '|', '*', '$', '%', '@', '!', '~', '`', '+', '&'], '', $name);
        $fName = time() . rand(10000, 99999) . '.' . get_file_ext($name);
        $path = $file->storeAs($folderName . '/' . date('Ym'), $fName, 'public');
        return $path;
    }
}
