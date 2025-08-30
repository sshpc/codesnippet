<?php
if ($key['name']) {
    //判断是否是全名 有空格 完整匹配
    if (strpos($key['name'], ' ') !== false) {
        //拆分为数组
        $name_array = explode(' ', $key['name']);
        //防止中间名
        $lastname = $name_array[count($name_array) - 1];
        $where['dc.firstname'] = array('like', "%$name_array[0]%");
        $where['dc.lastname'] = array('like', "%$lastname%");
    } else {
        $where['_complex'] = "dc.firstname like '%" . $key['name'] . "%' or dc.lastname like '%" . $key['name'] . "%'";
    }
}



//自动登录
$data['login_time'] = date("Y/m/d H:i:s", time());
$data['login_ip'] = get_ip();
$this->customer_model->upCustomerLoginTime($customInfo['id'], $data);
$_SESSION["clogin"] = 1;
$_SESSION["id"] = $customInfo['id'];
$_SESSION["_logtime"] = time();
$_SESSION["customer_group_id"] = $customInfo['customer_group_id']; //用户组

$selected = I('post.selected');


$msg = array(
    'status' => 1,
    'msg' => 'Password reset completed'
);
$this->ajaxReturn($msg);

$this->assign("REQUEST_URI", $_SERVER ['REQUEST_URI']);
$this->display('Index/sp_submittedList');

redirect(U("/"));

$data_u['token'] = getToken('cid/' . $customid);

$subject = '';
$content = "";

                $content .= L('text_reviewer_peer_review_agree_content_1') . "<br><br>";

                //添加登录链接  240919
                $token = $data_u['token'];
                $content .= "You can also click this <a style='text-decoration: revert;' href='".SET_WEB_URL."/Passport/Index/remodifypassword/t/". $token ."'><b>link</b></a> to log in to the submission system, where you can view your historical review records and submit your current review comments.";


                $message = '<html dir="ltr" lang="en">' . "\n";
            $message .= '  <head>' . "\n";
            $message .= '    <title>' . $subject . '</title>' . "\n";
            $message .= '    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
            $message .= '  </head>' . "\n";
            $message .= '  <body>' . html_entity_decode($content, ENT_QUOTES, 'UTF-8') . '</body>' . "\n";
            $message .= '</html>' . "\n";

            $sendData['fromMail'] = C('CONFIG_EMAIL');//系统邮箱
            $sendData['toMail'] = array($reviewer_email);//
            $sendData['ccMail'] = array($manuscript_info['email']);
            $sendData['mailSubject'] = $subject;
            $sendData['mailBody'] = $message;

            $email_type = 'Reviewer agrees to review - To Reviewer';
            $this->sendMailsec($sendData, 0, $email_type);


$array = M('customer')->where($where)->save($data);

$where['is_use'] = 1;
$orcidInfo = $this->user_model->getOneInfo('customer_orcid', $where);






    //作者信息
    $info['authorlist'] = getAuthor($id, 1);




    array_walk_recursive($data, function (&$item) {
                    $item = str_replace('"', '\"', $item);
                });


                array_walk_recursive($data, function (&$item) {
            if (is_string($item)) {
                $item = str_replace('&quot;', '"', $item);
            }
        });



        // 提取 sort 列
        $sortColumn = array_column($data, 'rankingweight');

        // 使用 array_multisort 函数进行降序排序
        array_multisort($sortColumn, SORT_DESC, $data);


        protected function getGitVersion(){
        // // 读取 COMMIT_EDITMSG 文件内容
        // $command = 'git log -1 --pretty=%B';
        // exec($command, $output);
        // $output = reset($output);
        // return $output;

        // 获取当前目录路径
        $currentDir = getcwd();
        // 判断是否存在 .git 目录
        if (is_dir($currentDir . '/.git')) {
            
             $commitFile = $currentDir . '/.git' . '/COMMIT_EDITMSG';
            // 检查文件是否存在且可读取
            if (!file_exists($commitFile) || !is_readable($commitFile)) {
                return false;
            }
    
            // 读取文件内容
            $output = file_get_contents($commitFile);

            return trim($output);
            
        } 
    }

    //获取git版本号
    public function getversion()
    {
        $output=$this->getGitVersion();

        $data_json = array(
            'status' => 1,
            'version' => $output

        );
        $this->ajaxReturn($data_json);
    }

    

    //获取git版本信息
    public function getGitinfobak20250819()
    {
        // 获取当前目录路径
        $currentDir = getcwd();

        // 判断是否存在 .git 目录
        if (is_dir($currentDir . '/.git')) {
            // 执行 git log 命令获取提交信息
            $command = 'git log -30 --pretty=format:"%s|%ad" --date=iso';
            exec($command, $commitList);
            $response = array();
            foreach ($commitList as $commit) {
                list($description, $date) = explode('|', $commit);
                $response[] = array(
                    'description' => $description,
                    'date' => $date
                );
            }
            $msg = "ok";
        } else {
            $msg = "未找到 .git 目录";
        }

        $msg = array(
            'msg' => $msg,
            'response' => $response
            
        );

        $this->ajaxReturn($msg);
    }

    //直接解析.git 目录下的文件（复杂但无需 Git）
    public function getGitinfo()
    {
        $currentDir = getcwd();
        $gitDir = $currentDir . '/.git';
        
        if (!is_dir($gitDir)) {
            $this->ajaxReturn(['msg' => '未找到 .git 目录', 'response' => []]);
            return;
        }
        
        // 读取HEAD指向的引用
        $headRef = trim(file_get_contents($gitDir . '/HEAD'));
        if (strpos($headRef, 'ref: ') === 0) {
            $refPath = $gitDir . '/' . substr($headRef, 5);
            $currentCommit = trim(file_get_contents($refPath));
        } else {
            $currentCommit = $headRef; // 分离头指针情况
        }
        
        $response = [];
        $commitCount = 0;
        $currentHash = $currentCommit;
        
        // 最多获取30个提交
        while ($currentHash && $commitCount < 30) {
            // 构建对象文件路径
            $objDir = $gitDir . '/objects/' . substr($currentHash, 0, 2);
            $objFile = $objDir . '/' . substr($currentHash, 2);
            
            if (!file_exists($objFile)) break;
            
            // 读取并解压对象内容
            $content = zlib_decode(file_get_contents($objFile));
            $firstSpace = strpos($content, ' ');
            $nullPos = strpos($content, "\0");
            
            $header = substr($content, 0, $firstSpace);
            $body = substr($content, $nullPos + 1);
            
            // 解析提交信息
            if ($header === 'commit') {
                $lines = explode("\n", $body);
                $description = '';
                $date = '';
                $inMessage = false;
                
                foreach ($lines as $line) {
                    if ($inMessage && trim($line) !== '') {
                        $description = trim($line);
                        $inMessage = false;
                    }
                    if (strpos($line, 'committer') === 0) {
                        preg_match('/\d+/', $line, $matches);
                        $date = date('Y-m-d H:i:s', $matches[0]);
                    }
                    if ($line === '') {
                        $inMessage = true;
                    }
                }
                
                $response[] = [
                    'description' => $description,
                    'date' => $date,
                    'hash' => $currentHash
                ];
                
                $commitCount++;
            }
            
            // 获取父提交
            preg_match('/^parent ([0-9a-f]+)/m', $body, $parentMatches);
            $currentHash = isset($parentMatches[1]) ? $parentMatches[1] : null;
        }
        
        $this->ajaxReturn([
            'msg' => 'ok',
            'response' => $response
        ]);
    }

    // 使用用户唯一标识（如IP或用户ID）作为 session key，避免不同客户端间数据混淆
    protected function getRedDotSessionKey() {
        // 假设未登录用户用IP，已登录用用户ID
        $userId = session('user_id');
        if ($userId) {
            return 'last_clicked_version_' . $userId;
        } else {
            return 'last_clicked_version_' . get_client_ip();
        }
    }

    public function checkRedDot(){
        $currentVersion = $this->getGitVersion();
        $sessionKey = $this->getRedDotSessionKey();
        $sessionVersion = session($sessionKey) ? session($sessionKey) : '';
        if ($currentVersion != $sessionVersion) {
            $this->ajaxReturn(['status' => 1]);
        }
        return false; // 无新版本或已点击，不显示红点
    }

    public function clickRedDot(){
        $currentVersion = $this->getGitVersion();
        $sessionKey = $this->getRedDotSessionKey();
        session($sessionKey, $currentVersion);
        $this->ajaxReturn(['status' => 1, 'msg' => '点击成功']);
    }






