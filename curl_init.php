<?php
//直接注册doi内容  250326
    public function registerDoiMetaData($ms_id=0){
        $ms_id = I('param.id/d');
        if(!$ms_id){ //非空验证
            return '';
        }
        $DoiMetaData = $this->getDoiMetaData($ms_id);
        // XML 数据
        $doi_metadata=$DoiMetaData[0];
        $xmlfilename=$DoiMetaData[1];

        // 创建一个临时文件来保存 XML 数据
        $temp_file = tmpfile();
        fwrite($temp_file, $doi_metadata);
        fseek($temp_file, 0);

        // 获取临时文件的元数据以获取路径
        $meta_data = stream_get_meta_data($temp_file);
        $temp_file_path = $meta_data['uri'];

        
        // 初始化 cURL 会话
        $ch = curl_init();
        //表单数据
        $post_data = array(
            'operation' => 'doMDUpload',
            'login_id' => 'xxx',
            'login_passwd' => 'xxx',
            'fname' => new \CURLFile($temp_file_path, 'application/xml', $xmlfilename.'.xml')
        );

        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_URL, 'https://test.crossref.org/servlet/deposit');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 跳过 SSL 验证，相当于 --insecure 选项
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


        // 执行 cURL 请求
        $response = curl_exec($ch);

        // 检查是否有错误
        if (curl_errno($ch)) {
            echo 'Curl error: '. curl_error($ch);
        }

        // 关闭 cURL 会话
        curl_close($ch);

        //file_put_contents('phpfiledump.txt', date('Y/m/d H:i:s', time()) . ' -- ' .json_encode($response).PHP_EOL ,FILE_APPEND);
        // 删除临时文件
        fclose($temp_file);

        
        // 如果响应有success字段，表示成功
        if (strpos($response, 'SUCCESS') !== false) {
            // 更新数据库中的 DOI 状态
            $this->journals_model->upDataInfo('manuscript',['id'=>$ms_id],['doi_status'=>1,'doi_status_time'=>date('Y-m-d H:i:s', time())]);
            $msg = array (
                "status" => 1,
                "msg" => "DOI提交注册成功"
            );
        }else {
            $msg = array (
                "status" => 0,
                "msg" => "DOI提交注册失败"
            );
        }
        
        $this->ajaxReturn($msg);
    
        
    }

    //验证DOI是否生效  250327
    public function checkDoi($ms_id=0){
        $ms_id = I('param.id/d');
        if(!$ms_id){ //非空验证
            return '';
        }
        
        $ms_info = $this->journals_model->getOneInfo('manuscript', ['id'=>$ms_id]);
        
        if($ms_info['doi_status'] == 2){
            $msg = array (
                "status" => 1,
                "msg" => "DOI已经生效"
            );
        }else{
            //向crossref查询DOI是否生效
            //https://api.crossref.org/works/10.21926/obm.geriatr.2501304/agency
            // 初始化 cURL 会话
            $ch = curl_init();

            // 设置 cURL 选项
            curl_setopt($ch, CURLOPT_URL, 'https://api.crossref.org/works/'.$ms_info['doi'].'/agency');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 将结果返回为字符串而不是直接输出
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 禁用 SSL 证书验证，仅在测试或自签名证书等情况下使用，生产环境建议正确配置证书验证

            // 执行 cURL 请求
            $data = curl_exec($ch);

            // 检查是否有错误
            if (curl_errno($ch)) {
                echo 'cURL Error: '. curl_error($ch);
            } else {
                 // 检查是否返回了 "Resource not found."
                if ($data == 'Resource not found.') {
                    $msg = array (
                        "status" => 0,
                        "msg" => "DOI未生效"
                    );
                } else {
                    // 更新数据库中的 DOI 状态
                    $this->journals_model->upDataInfo('manuscript',['id'=>$ms_id],['doi_status'=>2,'doi_status_time'=>date('Y-m-d H:i:s', time())]);
                    $msg = array (
                        "status" => 1,
                        "msg" => "DOI已经生效"
                    );
                }
            }
        }
        $this->ajaxReturn($msg);
        
    }
