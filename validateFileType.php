<?php

    /**
     * 强制判断文件类型是否符合  250331 250409
     *
     * @param array $info 文件信息数组，包含文件路径和文件类型  一般是 $info = $Upload->upload($_FILES);
     * @param array $allowedTypes 允许的文件类型数组，例如 ['jpg', 'png', 'gif']
     * @return bool true 类型符合，false 类型不符合
     */
    protected function validateFileType($info, $allowedTypes) {
        // 获取文件路径
        $filePath = DIR_DOWNLOAD.'/uploads/'.$info['Filedata']['savepath'] . $info['Filedata']['savename'];
        // 检查文件是否存在
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        // 以二进制模式打开文件
        $file = @fopen($filePath, 'rb');
        if (!$file) {
            return false;
        }
        // 将文件指针重置到文件开头
        rewind($file);

        // 读取文件的前 32 个字节
        $signature = fread($file, 32);
        fclose($file);

        // 定义常见文件类型的签名
        $signatures = [
            "\xFF\xD8\xFF" => 'jpg',
            "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" => 'png',
            "\x47\x49\x46\x38\x37\x61" => 'gif',
            "\x47\x49\x46\x38\x39\x61" => 'gif',
            "\x25\x50\x44\x46" => 'pdf',
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" => 'doc',
            "\x50\x4B\x03\x04\x14\x00\x06\x00" => 'docx',
            "\x52\x61\x72\x21\x1A\x07\x00" => 'rar',
            "\x50\x4B\x03\x04" => 'zip',
            "\x25\x54\x45\x58" => 'tex',
            "\x2C" => 'csv',
            "\x00\x00\x00\x18\x66\x74\x79\x70" => 'mp4',
            "\x49\x44\x33" => 'mp3',
        ];

        // 获取文件后缀
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // 检查文件签名
        foreach ($signatures as $sig => $type) {
            if (strncmp($signature, $sig, strlen($sig)) === 0) {
                // 转换为小写以进行不区分大小写的比较
                $lowercaseType = strtolower($type);
                foreach ($allowedTypes as $allowed) {
                    if ($lowercaseType === strtolower($allowed) && $fileExtension === $lowercaseType) {
                        return true;
                    }
                }
                return false;
            }
        }

        return false;
    }


    //重置密码提交  240823
    public function doremodifypassword()
    {
        $token = I("post.token");

        $newPassword = I("post.newPassword");

        $paramArr = decryptToken($token);

        $where_c['id'] = $paramArr['cid'];
        $customInfo = $this->customer_model->getOneInfo('customer', $where_c);
        if ($customInfo && $customInfo['token'] == $token) {

            $salt = $customInfo['salt'];
            $newPassword = sha1($salt . sha1($salt . sha1($newPassword)));

            $data_u['id'] = $customInfo['id'];
            $data_u['password'] = $newPassword;
            //更改成功后清除token
            $data_u['token']='';
            $result_u = $this->customer_model->upDataInfo('customer', $where_c, $data_u);
            if ($result_u) {

                //自动登录
                $data['login_time'] = date("Y/m/d H:i:s", time());
                $data['login_ip'] = get_ip();
                $this->customer_model->upCustomerLoginTime($customInfo['id'], $data);
                $_SESSION["clogin"] = 1;
                $_SESSION["id"] = $customInfo['id'];
                $_SESSION["_logtime"] = time();
                $_SESSION["customer_group_id"] = $customInfo['customer_group_id']; //用户组


                $msg = array(
                    'status' => 1,
                    'msg' => 'Password reset completed'
                );
                $this->ajaxReturn($msg);
            }
        }
        $msg = array(
            'status' => 0,
            'msg' => 'Password reset failed'
        );

        $this->ajaxReturn($msg);
    }
