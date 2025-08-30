
    <?php

namespace User\Controller;

use Common\Controller\FrontController;

class OrcidController extends FrontController
{
    protected $journals_model = null;
    protected $user_model = null;
    protected $customer_model = null;
    /**
     * 构造器
     */
    public function __construct()
    {
        parent::__construct();
        $this->journals_model = new \Common\Model\JournalsModel();
        $this->user_model = new \Common\Model\UserModel();
        $this->customer_model = new \Common\Model\CustomerModel();
    }

    private function getORCID($_code = 0)
    {

        //Initialize cURL session
        $orcid_Client_ID = ORCID_ID;   //企业版本Client_ID
        $orcid_Client_secret = ORCID_SECRET;
        $orcid_resource_url = 'https://' . ORCID_RESOURCE_URL . '/oauth/token';

        $_orcid_post = "client_id=" . $orcid_Client_ID . "&client_secret=" . $orcid_Client_secret . "&grant_type=authorization_code&code=" . $_code;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $orcid_resource_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_orcid_post);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            file_put_contents('phpfiledump.txt', json_encode('curl_err:' . $ch) . PHP_EOL, FILE_APPEND);
        }
        curl_close($ch);
        unset($ch);
        $_result = json_decode($result, true); //序列化数组结果

        return $_result;
    }



    //模拟登录
    private function loginSimulation($cid)
    {
        $datacu['login_time'] = date("Y/m/d H:i:s", time());
        $datacu['login_ip'] = get_ip();
        $datacu['approved'] = 1; //设置激活
        M('customer')->where(['id' => $cid])->save($datacu);
        $_SESSION["clogin"] = 1;
        $_SESSION["id"] = $cid;
        $_SESSION["_logtime"] = time();
    }

    //个人信息页绑定回调  240920
    public function bindingCallback()
    {
        $code = explode('?code=', $_SERVER['REQUEST_URI'])[1];

        if (!$code) {
            echo 'code is null';
            exit();
        }

        //检查是否重复绑定
        $where['c_id'] = $_SESSION["id"];
        $orcidInfo = $this->user_model->getOneInfo('customer_orcid', $where);
        //存在则标记失效
        if ($orcidInfo) {
            M('customer_orcid')->where(['c_id' => $_SESSION["id"]])->save(['is_use' => 0]);
        }

        $_result = $this->getORCID($code);

        //检查是否已绑定
        $orcidInfos = $this->user_model->getOneInfo('customer_orcid', ['orcid'=>$_result['orcid'],'is_use'=>1]);
        if($orcidInfos){
            //存在则提示已绑定
            $customerinfo=$this->user_model->getOneInfo('customer', ['id'=>$orcidInfos['c_id']]);
            echo 'The orcid account has been tied to another account. <b>'.$customerinfo['email'] .'</b> Please unbind and try again.';
            exit();
        }

        if ($_result['access_token']) { //获取到正确得结果时
            //写入orcid表
            $data['c_id'] = $_SESSION['id'];
            $data['orcid'] = $_result['orcid'];
            $data['scope'] = $_result['scope'];
            $data['name'] = $_result['name'];
            $data['expires_in'] = $_result['expires_in'];
            $data['access_token'] = $_result['access_token'];
            $data['refresh_token'] = $_result['refresh_token'];
            $data['is_use'] = 1;
            $data['add_time'] = date('Y-m-d H:i:s');

            M('customer_orcid')->data($data)->add();

            //写入用户操作日志
            M('customer_operation_log')->add(['c_id' => $where['c_id'], 'method' => 'binding', 'type' => 'myProfile', 'remark' => 'Orcid', 'add_time' => date("Y/m/d H:i:s", time())]);

            redirect('/User/Index/myProfile');
        } else { //无法获取到orcid id信息时，跳转url操作
            //echo ' <pre> ';
            //var_dump($_result);
            file_put_contents('phpfiledump.txt', date("Y/m/d H:i:s", time()) . ' -- Binding failed ' . json_encode($_result ).PHP_EOL ,FILE_APPEND);

            echo 'Binding failed. please contact info@xxx.com';
            die();
        }
    }

    //解绑
    public function UnLink()
    {
        //判断是否已登录
        if (session('id')) {
            $id = I('id', '', 'trim');
            $orcidInfo = $this->user_model->getOneInfo('customer_orcid', ['id' => $id]);
            //存在则标记失效
            if ($orcidInfo) {
                M('customer_orcid')->where(['id' => $id])->save(['is_use' => 0]);

                //向orcid发解绑请求  241008
                $orcid_resource_url = 'https://' . ORCID_RESOURCE_URL . '/oauth/revoke';

                $_orcid_post = [
                    "client_id" => ORCID_ID,
                    "client_secret" => ORCID_SECRET,
                    "token" => $orcidInfo['access_token']
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $orcid_resource_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_orcid_post));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                $headers = array();
                $headers[] = 'Accept: application/json';
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                    file_put_contents('phpfiledump.txt', json_encode('curl_err:' . $ch) . PHP_EOL, FILE_APPEND);
                }
                curl_close($ch);
                unset($ch);

                //写入用户操作日志
                M('customer_operation_log')->add(['c_id' => $orcidInfo['c_id'], 'method' => 'UnLink', 'type' => 'myProfile', 'remark' => 'Orcid', 'add_time' => date("Y/m/d H:i:s", time())]);
            }
        }
        redirect('/User/Index/myProfile');
    }

    //三方登录回调
    public function loginCallback()
    {
        $code = explode('?code=', $_SERVER['REQUEST_URI'])[1];

        if (!$code) {
            echo 'code is null';
            exit();
        }

        $_result = $this->getORCID($code);

        if ($_result['orcid']) { //获取到正确得结果时

            //检查是否已绑定
            $where['orcid'] = $_result['orcid'];
            $where['is_use'] = 1;
            $orcidInfo = $this->user_model->getOneInfo('customer_orcid', $where);

            //存在则直接登录
            if ($orcidInfo) {
                $this->loginSimulation($orcidInfo['c_id']);
                redirect('/User/Index/Index');
            }

            session('orcid_result', json_encode($_result));
            $this->display('Orcid/sp_judge');
        } else { //无法获取到orcid id信息时，跳转url操作
            //echo ' <pre> ';
            //var_dump($_result);
            file_put_contents('phpfiledump.txt', date("Y/m/d H:i:s", time()) . ' -- login failed ' . json_encode($_result ).PHP_EOL ,FILE_APPEND);
            echo 'login failed. please contact info@xxx.com';
            die();
        }
    }

    //三方登录未绑定验证是否存在用户
    public function doJudge()
    {
        $email = I('email', '', 'trim');
        $firstname = I('firstname', '', 'trim');
        $lastname = I('lastname', '', 'trim');
        session('firstname', $firstname);
        session('lastname', $lastname);
        session('email', $email);

        $orcid_result = session('orcid_result');
        $where['email'] = $email;
        $customerInfo = $this->user_model->getOneInfo('customer', $where);

        if ($customerInfo) { //有用户发信验证

            $token = getToken('cid/' . $customerInfo['id'] . '/orcid_result/' . base64_encode($orcid_result));

            $data_u['token'] = $token;
            $where_u['id'] = $customerInfo['id'];
            $this->customer_model->upDataInfo('customer', $where_u, $data_u);

            $subject = 'xxx Publishing Inc.丨The Open Access Publisher - ORCID Link Request';
            $content = "";

            //调用jobname 
            $jobwhere['job_title_id'] = array('eq', $customerInfo['job_title_id']);
            $jobname = M('job_title')->field('name')->where($jobwhere)->find();
            $jobname = $jobname['name'] ? $jobname['name'] : '';

            $content = "";
            $subject = 'xxx Publishing Inc.丨The Open Access Publisher - Thank you for registering';
            $content .= "Dear " . $jobname . ' ' . $lastname . " ,<br><br>";
            $orcidarr = json_decode($orcid_result);
            $orcid = $orcidarr->orcid;
            $content .=  "We have received a request to link the ORCID (ID: $orcid) to your xxx account. Please click <a style='text-decoration: revert;' href='" . SET_WEB_URL . "/User/Orcid/existingUsersVerify/t/" . $token . "'><b>link</b></a> to confirm the request." . "<br><br>";

            $content .= "If you did not initiate this request or need further assistance, please contact us at <a href='mailto:info@xxx.com'>info@xxx.com</a>.<br><br>";

            $content .= $this->getSendEmailFooter();

            $message = '<html dir="ltr" lang="en">' . "\n";
            $message .= '  <head>' . "\n";
            $message .= '    <title>' . $subject . '</title>' . "\n";
            $message .= '    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
            $message .= '  </head>' . "\n";
            $message .= '  <body>' . html_entity_decode($content, ENT_QUOTES, 'UTF-8') . '</body>' . "\n";
            $message .= '</html>' . "\n";

            $sendData['fromMail'] = C('CONFIG_EMAIL'); //系统邮箱
            $sendData['toMail'] = array($email);

            $sendData['mailSubject'] = $subject;
            $sendData['mailBody'] = $message;

            $status = $this->sendMailsec($sendData, 0, 'orcid by doJudge');
            $url = '';
            if ($status) {
                $url = '/User/Orcid/sendSuccessMail';
                //写入日志


            } else {
                $msg = array(
                    'status' => 0,
                    'msg' => 'send mail error',
                    'url' => $url
                );
                $this->ajaxReturn($msg);
            }
        } else { //无用户跳转专用注册页

            $url = '/User/Orcid/userRegister';
        }

        //session('orcid_result', $orcid_result);
        //session('email', $email);

        $msg = array(
            'status' => 1,
            'msg' => 'success',
            'url' => $url
        );
        $this->ajaxReturn($msg);
    }

    //发信成功提醒页
    public function sendSuccessMail()
    {
        $this->assign('email', session('email'));
        $this->display('Orcid/sp_sendsuccessmail');
    }

    //已有用户 通过信链接点击验证
    public function existingUsersVerify()
    {

        $token = I("t", '', 'trim');
        $paramArr = decryptToken($token);

        $where_c['id'] = $paramArr['cid'];

        $orcid_result = base64_decode($paramArr['orcid_result']);

        $customInfo = $this->customer_model->getOneInfo('customer', $where_c);
        if ($customInfo && $customInfo['token'] == $token) {
            //通过验证 入库orcid信息和直接登录

            //写入orcid表
            $_result = json_decode($orcid_result);

            //对象转关联数组
            $_result = get_object_vars($_result);

            $data['c_id'] = $where_c['id'];
            $data['orcid'] = $_result['orcid'];
            $data['scope'] = $_result['scope'];
            $data['name'] = $_result['name'];
            $data['expires_in'] = $_result['expires_in'];
            $data['access_token'] = $_result['access_token'];
            $data['refresh_token'] = $_result['refresh_token'];
            $data['is_use'] = 1;
            $data['add_time'] = date('Y-m-d H:i:s');



            M('customer_orcid')->data($data)->add();

            //清空token以防重复点击  241009
            M('customer')->where(['id' => $where_c['id']])->save(['token' => '']);

            $this->loginSimulation($where_c['id']);


            redirect('/User/Index/Index');
        } else {
            //echo $customInfo['token'];
            //echo $token;
            redirect(U("/Home/Index/error"));
        }
    }

    //专用注册页
    public function userRegister()
    {

        $country_list = $this->customer_model->getInfoArray('country', '', 'name asc');
        $this->assign('country_list', $country_list);
        $this->assign('media_title', 'New User Registration' . $this->default_title);

        $this->assign('firstname', session('firstname'));
        $this->assign('lastname', session('lastname'));
        $this->assign('email', session('email'));

        $this->display('Orcid/sp_userRegister');
    }

    //注册提交
    public function doUserRegister()
    {

        $job_title_id = I("post.job_title_id", '', 'trim');
        $password = I("post.password", '', 'trim');
        $firstname = I('post.firstname');
        $lastname = I('post.lastname');
        $country_id = I('post.country_id');
        $organization = I('post.organization');
        $email = I('post.email');
        //密码加密
        //$password=md5(strrev(md5($password)));
        $salt = substr(md5(uniqid(rand(), true)), 0, 9);
        $password = sha1($salt . sha1($salt . sha1($password)));

        $where['email'] = $email;
        $id = $this->customer_model->getCustomer($where);
        if ($id) {
            $msg = array(
                "status" => '0',
                "msg" => 'E-Mail address is already registered!'
            );
            $this->ajaxReturn($msg);
        }

        $data['job_title_id'] = $job_title_id;
        $data['password'] = $password;
        $data['salt'] = $salt;
        $data['firstname'] = $firstname;
        $data['lastname'] = $lastname;
        $data['country_id'] = $country_id;
        $data['organization'] = $organization;
        $data['email'] = $email;
        $data['date_added'] = date("Y/m/d H:i:s", time());
        $data['ip'] = get_ip();
        $data['approved'] = 0; //默认不批准

        //添加
        $msg = "Success!";
        $u_id = $this->customer_model->addInfo('customer', $data);
        if ($u_id) {

            //检查是否是作者  240925
            $auinfo = M('ms_author')->where(['email' => $email])->find();
            if ($auinfo) {
                M('ms_author')->where(['id' => $auinfo['id']])->save(['c_id' => $u_id]);
            }

            //检查是否是已提交审稿人  241011
            $srinfo = M('submitted_reviewer dsr')->field('dsr.id')->join('ds_reviewer dr ON dsr.r_id = dr.id ')->where(['dr.email' => $email,'dsr.c_id' =>['exp','IS NULL']])->find();
            if ($srinfo) {
                M('submitted_reviewer')->where(['id' => $srinfo['id']])->save(['c_id' => $u_id]);
            }

            $orcid_result = session('orcid_result');

            $token = getToken('cid/' . $u_id . '/orcid_result/' . base64_encode($orcid_result));

            $data_u['token'] = $token;
            $where_u['id'] = $u_id;
            $this->customer_model->upDataInfo('customer', $where_u, $data_u);

            //调用jobname 
            $jobwhere['job_title_id'] = array('eq', $job_title_id);
            $jobname = M('job_title')->field('name')->where($jobwhere)->find();
            $jobname = $jobname['name'] ? $jobname['name'] : '';

            $content = "";
            $subject = 'xxx Puisher - Thank you for registering';
            $content .= "Dear " . $jobname . ' ' . $lastname . " ,<br><br>";
            $content .=  'Welcome and thank you for registering at xxx Publishing Inc.' . "<br><br>";

            //添加登录链接  240919

            $content .= "Please click on the following <a style='text-decoration: revert;' href='" . SET_WEB_URL . "/User/Orcid/unExistingUsersVerify/t/" . $token . "'><b>link</b></a> to validate your account. The link will expire after 24 hours.<br><br>";

            $content .= "After verification, your ORCID will be automatically linked to your xxx account.<br><br>";


            $content .= $this->getSendEmailFooter();

            $message = '<html dir="ltr" lang="en">' . "\n";
            $message .= '  <head>' . "\n";
            $message .= '    <title>' . $subject . '</title>' . "\n";
            $message .= '    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
            $message .= '  </head>' . "\n";
            $message .= '  <body>' . html_entity_decode($content, ENT_QUOTES, 'UTF-8') . '</body>' . "\n";
            $message .= '</html>' . "\n";

            $sendData['fromMail'] = C('CONFIG_EMAIL'); //系统邮箱
            $sendData['toMail'] = array($email); //发送给当前审稿人

            $sendData['mailSubject'] = $subject;
            $sendData['mailBody'] = $message;

            $status = $this->sendMailsec($sendData, 0, 'orcid by doJudge');
            $url = '';
            if ($status) {
                $url = '/User/Orcid/sendSuccessMail';
                //写入日志


            } else {
                $msg = array(
                    'status' => 0,
                    'msg' => 'send mail error',
                    'url' => $url
                );
                $this->ajaxReturn($msg);
            }
        } else {
            file_put_contents('phpfiledump.txt', date("Y/m/d H:i:s", time()) . ' -- Register failed ' . json_encode($data).PHP_EOL ,FILE_APPEND);

            echo 'Register failed. please contact info@xxx.com';
            die();
        }


        $msg = array(
            'status' => 1,
            'msg' => 'success',
            'url' => $url
        );
        $this->ajaxReturn($msg);
    }

    //未有用户注册 通过信链接点击验证
    public function unExistingUsersVerify()
    {

        $token = I("t", '', 'trim');
        $paramArr = decryptToken($token);

        $orcid_result = base64_decode($paramArr['orcid_result']);

        $customInfo = $this->customer_model->getOneInfo('customer', ['id' => $paramArr['cid']]);
        if ($customInfo && $customInfo['token'] == $token) {
            //通过验证 入库orcid信息和直接登录
            $_result = json_decode($orcid_result);

            //对象转关联数组
            $_result = get_object_vars($_result);

            $data['c_id'] = $paramArr['cid'];
            $data['orcid'] = $_result['orcid'];
            $data['scope'] = $_result['scope'];
            $data['name'] = $_result['name'];
            $data['expires_in'] = $_result['expires_in'];
            $data['access_token'] = $_result['access_token'];
            $data['refresh_token'] = $_result['refresh_token'];
            $data['is_use'] = 1;
            $data['add_time'] = date('Y-m-d H:i:s');
            M('customer_orcid')->data($data)->add();

            //清空token以防重复点击  241009
            M('customer')->where(['id' => $paramArr['cid']])->save(['token' => '']);

            //模拟登录
            $this->loginSimulation($paramArr['cid']);
            redirect('/User/Index/Index');
        } else {
            //echo $customInfo['token'];
            //echo $token;
            redirect(U("/Home/Index/error"));
        }
    }
}
