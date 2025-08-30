<?php
namespace Obm\Controller;

use Think\Controller;

class StatisticsController extends Controller
{

    //获取到所有的期刊
    protected function getAllJournalList()
    {
        $order_by = 'sort_order ASC';
        $where['is_use'] = 1;
        $result = M('journals')->field('id,url as en_title')
            ->order($order_by)
            ->where($where)
            ->select();
        return $result;
    }

    /***
     * 查询某一年的每月统计
     * sql过于复杂 包含（子查询，虚拟表，分组查询，链接查询，函数）
     * @param string $table 表名
     * @param string $timefield
     * @param int $year
     * @param string $where
     * return array 索引数组
     */
    protected function getList($table, $timefield, $year, $journal_id, $journal_field, $where = '')
    {
        $journal_id = $journal_id != 0 ? 'INNER JOIN `ds_journals` dj ON dn.' . $journal_field . '=dj.id AND dj.id=' . $journal_id : '';

        $sql = "SELECT months.month, COALESCE(dnews.total, 0) AS total FROM ( SELECT '01' AS MONTH UNION SELECT '02' UNION SELECT '03' UNION SELECT '04' UNION SELECT '05' UNION SELECT '06' UNION SELECT '07' UNION SELECT '08' UNION SELECT '09' UNION SELECT '10' UNION SELECT '11' UNION SELECT '12') AS months LEFT JOIN ( SELECT DATE_FORMAT(dn.$timefield,'%m') AS MONTH, COUNT(dn.id) AS total FROM $table dn $journal_id WHERE YEAR(dn.$timefield) = $year $where  GROUP BY DATE_FORMAT(dn.$timefield,'%m') ) AS dnews ON months.month = dnews.month ORDER BY months.month";

        $resulted = M()->query($sql);
        //sqldump();

        $resultotal = array();
        foreach ($resulted as $k => $v) {
            array_push($resultotal, $v['total']);
        }
        return $resultotal;
    }
    //折线图首页
    public function index()
    {
        setlog();
        $this->assign('JournalList', $this->getAllJournalList());
        $this->display();
    }
    //统计表格页
    public function table()
    {
        setlog();
        $this->assign('JournalList', $this->getAllJournalList());
        $this->display();
    }
    //图表整合页
    public function chart()
    {
        setlog();
        $this->assign('JournalList', $this->getAllJournalList());
        $this->display();
    }

    //统计图表方法
    public function getchartdata()
    {
        //实例化模型
        $model = M();
        $table = '';
        $join = '';
        $field = '';
        $where = [];
        $order = '';
        $group = '';
        $having = '';
        $key = I('param.'); //获取用户提交的数据
        switch ($key['type']) {
            case '0':
                exit;
                break;
            case 'manuscript':
                $field = 'dj.`url` as name ,COUNT(dm.id) AS total';
                $table = 'ds_manuscript dm';
                $join = 'left join ds_journals dj ON dm.journal_id = dj.id';
                $group = 'dj.`url`';

                if ($key['is_published'] == '1' || $key['is_published'] == '0') {
                    $where['dm.is_published'] =  intval($key['is_published']);
                }
                if ($key['is_free'] == '1' || $key['is_free'] == '0') {
                    $where['dm.is_free'] = intval($key['is_free']);
                }

                break;
            case 'ArticleType':
                $field = 'dat.name,COUNT(dm.id) AS total';
                $table = 'ds_manuscript dm';
                $join = 'left join ds_article_type dat ON dm.article_type_id=dat.id left join ds_journals dj ON dm.journal_id = dj.id';
                $group = 'dat.name';

                if ($key['is_published'] == '1' || $key['is_published'] == '0') {
                    $where['dm.is_published'] =  intval($key['is_published']);
                }
                if ($key['is_free'] == '1' || $key['is_free'] == '0') {
                    $where['dm.is_free'] = intval($key['is_free']);
                }

                break;

            case 'eb':
                $field = 'dj.`url` as name ,COUNT(dce.id) AS total';
                $table = 'ds_customer_eb dce';
                $join = 'LEFT JOIN ds_customer dc ON dc.id = dce.c_id LEFT JOIN ds_journals dj ON dce.j_id=dj.id';
                $group = 'dj.`url`';
                $where['dc.approved'] = 1;
                break;
            case 'ge':
                $field = 'dj.`url` as name ,COUNT(dcg.id) AS total';
                $table = 'ds_customer_ge dcg';
                $join = 'LEFT JOIN ds_customer dc ON dc.id = dcg.c_id LEFT JOIN ds_journals dj ON dcg.j_id=dj.id';
                $group = 'dj.`url`';
                $where['dc.approved'] = 1;
                $where['dcg.s_id'] = array('gt', 1);

                break;
            case 'special_issue':
                $field = 'dj.`url` as name ,COUNT(dsi.id) AS total';
                $table = 'ds_special_issue dsi';
                $join = 'LEFT JOIN ds_journals dj ON dsi.journal_id=dj.id ';
                $group = 'dj.`url`';
                break;
            case 'rb':
                $field = 'dj.`url` as name ,COUNT(dcr.id) AS total';
                $table = 'ds_customer_re dcr';
                $join = 'LEFT JOIN ds_customer dc ON dc.id = dcr.c_id LEFT JOIN ds_journals dj ON dcr.j_id=dj.id';
                $group = 'dj.`url`';
                $where['dc.approved'] = 1;
                break;
        }

        switch ($key['wheretime']) {

            case 'date_published':
                $where['dm.date_published']  = array('between', array($key['s'], $key['e']));
                break;
            case 'date_submit':
                $where['dm.date_submit']  = array('between', array($key['s'], $key['e']));
                break;
            case 'date_deadline':
                $where['dsi.date_deadline']  = array('between', array($key['s'], $key['e']));
                break;
            case 'EB_addtime':
                $where['dce.addtime']  = array('between', array($key['s'], $key['e']));
                break;
            case 'GE_addtime':
                $where['dcg.addtime']  = array('between', array($key['s'], $key['e']));
                break;
            case 'RB_addtime':
                $where['dcr.addtime']  = array('between', array($key['s'], $key['e']));
                break;
        }

        if ($key['journal']) {
            $where['dj.id'] = (int)$key['journal'];
        }

        $where['dj.is_use'] = 1;

        try {
            //执行
            $data = $model
                ->field($field)
                ->table($table)
                ->join($join)
                //->page($page, $limit)
                ->where($where)
                ->group($group)
                ->having($having)
                ->order($order)
                ->select();

            

            $msg = 'success';
        }
        // 捕获异常
        catch (\Exception $e) {
            //echo M()->getLastSql();
            //die();
            //$msg = 'Message: ' . $e->getMessage();
            $data='';
            $msg = '操作有误';
            
        }
        $sql = $model->getLastSql();

        $data_json = array(
            'msg' => $msg,
            'data' => $data,
            'sql' => $sql
        );
        echo (strip_tags(htmlspecialchars_decode(json_encode($data_json))));
    }

    //统计表格方法
    public function gettabledata()
    {
        //$page = I('get.page') ? I('get.page') : 1;
        //$limit = I('get.limit') ? I('get.limit') : 10;
        //实例化模型
        $model = M();
        $table = '';
        $join = '';
        $field = '';
        $where = [];
        $order = '';
        $group = '';
        $having = '';
        $key = I('param.'); //获取用户提交的数据
        switch ($key['type']) {
            case '0':
                exit;
                break;
            case 'dmacountrytotal':
                $field = 'dj.`url` as "journal",dco.`name` as "name" ,COUNT(dma.id) AS total';
                $table = 'ds_journals dj';
                $join = ' CROSS JOIN `ds_country` dco LEFT JOIN ds_manuscript dm ON dm.`journal_id` = dj.id LEFT JOIN `ds_ms_author` dma  ON dma.`ms_id` = dm.`id` AND dma.`country_id`=dco.`id` AND dma.`correspond`=1';
                $group = 'dj.`url`, dco.`name`';
                $having = 'total>0';
                if ($key['is_published'] == '1' || $key['is_published'] == '0') {
                    $where['dm.is_published'] = $key['is_published'];
                }
                break;

            case 'datmstotal':
                $field = 'dj.`url` as "journal",dat.`name` as "name" ,COUNT(dm.id) AS total';
                $table = 'ds_journals dj';
                $join = ' CROSS JOIN `ds_article_type` dat LEFT JOIN ds_manuscript dm ON dm.`journal_id` = dj.id  AND dm.`article_type_id` = dat.`id` AND dat.`is_use`=1';
                $group = 'dj.`url`, dat.`name`';
                $having = 'total>0';
                if ($key['is_published'] == '1' || $key['is_published'] == '0') {
                    $where['dm.is_published'] = $key['is_published'];
                }
                break;
        }

        switch ($key['wheretime']) {

            case 'date_published':
                $where['dm.date_published']  = array('between', array($key['s'], $key['e']));
                break;
            case 'date_submit':
                $where['dm.date_submit']  = array('between', array($key['s'], $key['e']));
                break;
        }


        if ($key['journal']) {
            $where['dj.id'] = (int)$key['journal'];
        }

        $where['dj.is_use'] = 1;

        try {
            //执行
            $data = $model
                ->field($field)
                ->table($table)
                ->join($join)
                //->page($page, $limit)
                ->where($where)
                ->group($group)
                ->having($having)
                ->order($order)
                ->select();


            $sql = $model->getLastSql();

            $count = count($data);
            $code = 0;
            $msg = 'success';
        }
        // 捕获异常
        catch (\Exception $e) {
            echo M()->getLastSql();
            die();
            //$msg = 'Message: ' . $e->getMessage();
            $code = 1;
            $msg = '操作有误,条件不允许';
        }

        $data_json = array(
            'code' => $code,
            'msg' => $msg,
            'count' => $count,
            'data' => $data,
            'sql' => $sql
        );

        $this->ajaxReturn($data_json);
        //echo (strip_tags(htmlspecialchars_decode(json_encode($data_json))));
    }

    //简单信息
    public function getAbstractInfo()
    {
        $msg['msfree'] = M('manuscript')
            ->alias('dm')
            ->field("COUNT(dm.id) AS total")
            ->join('LEFT JOIN ds_journals dj ON dm.journal_id=dj.id ')
            ->where("dm.is_published=1 AND dm.is_free=1 AND dj.is_use=1")
            ->find();
        $msg['msunfree'] = M('manuscript')
            ->alias('dm')
            ->field("COUNT(dm.id) AS total")
            ->join('LEFT JOIN ds_journals dj ON dm.journal_id=dj.id ')
            ->where("dm.is_published=1 AND dm.is_free=0 AND dj.is_use=1")
            ->find();
        $msg['msall'] = M('manuscript')
            ->alias('dm')
            ->field("COUNT(dm.id) AS total")
            ->join('LEFT JOIN ds_journals dj ON dm.journal_id=dj.id ')
            ->where("dm.is_published=1 AND dj.is_use=1")
            ->find();
        $msg['siunclosed'] = M('special_issue')
            ->alias('si')
            ->field("COUNT(si.id) AS total")
            ->join('LEFT JOIN ds_journals dj ON si.journal_id=dj.id ')
            ->where("si.is_use=1 AND si.status=1 AND dj.is_use=1")
            ->find();
        $msg['sicloesd'] = M('special_issue')
            ->alias('si')
            ->field("COUNT(si.id) AS total")
            ->join('LEFT JOIN ds_journals dj ON si.journal_id=dj.id ')
            ->where("si.is_use=1 AND si.status=0 AND dj.is_use=1")
            ->find();
        $msg['siall'] = M('special_issue')
            ->alias('si')
            ->field("COUNT(si.id) AS total")
            ->join('LEFT JOIN ds_journals dj ON si.journal_id=dj.id ')
            ->where("si.is_use=1 AND dj.is_use=1")
            ->find();
        $msg['eball'] = M('customer_eb')
            ->alias('dce')
            ->field("COUNT(dce.id) AS total")
            ->join('LEFT JOIN ds_customer dc ON dc.id = dce.c_id LEFT JOIN ds_journals dj ON dce.j_id=dj.id ')
            ->where("dj.is_use=1 and dc.approved=1")
            ->find();
        $msg['geall'] = M('customer_ge')
            ->alias('dcg')
            ->field("COUNT(dcg.id) AS total")
            ->join('LEFT JOIN ds_customer dc ON dc.id = dcg.c_id LEFT JOIN ds_journals dj ON dcg.j_id=dj.id ')
            ->where("dj.is_use=1 and dc.approved=1 and dcg.s_id > 0")

            ->find();
        $msg['rball'] = M('customer_re')
            ->alias('dcr')
            ->field("COUNT(dcr.id) AS total")
            ->join('LEFT JOIN ds_customer dc ON dc.id = dcr.c_id LEFT JOIN ds_journals dj ON dcr.j_id=dj.id ')
            ->where("dj.is_use=1 and dc.approved=1")

            ->find();
        $msg['auall'] = M('ms_author')
            ->alias('si')
            ->field("COUNT(si.id) AS total")
            ->where("si.ms_id>0")
            ->find();
        $msg['aucorrespond'] = M('ms_author')
            ->alias('si')
            ->field("COUNT(si.id) AS total")
            ->where("si.ms_id>0 AND si.correspond=1")
            ->find();


        $this->ajaxReturn($msg);
    }



    //用户来源地区
    public function getCustomerInCountry()
    {
        $msg = M('customer')
            ->alias('dc')
            ->field("dco.`name` AS NAME,COUNT(dc.id) AS VALUE")
            ->join('`ds_country` dco ON dc.`country_id`=dco.`id`')
            ->group("dco.`name`")
            ->order('VALUE DESC')
            ->limit(0, 10)
            ->select();
        $this->ajaxReturn($msg);
    }

    //作者来源地区
    public function getAuthorInCountry()
    {
        $msg = M('ms_author')
            ->alias('dc')
            ->field("dco.`name` AS NAME,COUNT(dc.id) AS VALUE")
            ->join('`ds_country` dco ON dc.`country_id`=dco.`id`')
            ->group("dco.`name`")
            ->order('VALUE DESC')
            ->limit(0, 10)
            ->select();
        $this->ajaxReturn($msg);
    }


    //文章分期刊访问量
    public function getViewedOfManuscriptInJournal()
    {
        $msg = M('manuscript')
            ->alias('dm')
            ->field("dj.url AS name,SUM(dm.`viewed`) AS value")
            ->join("ds_journals dj ON dj.id = dm.journal_id")
            ->group("dm.`journal_id`")
            ->where("dm.`is_published`=1 AND dj.`is_use`=1")
            ->select();
        $this->ajaxReturn($msg);
    }




    //按刊统计文章已发布数量
    public function getPublishedOfManuscriptInJournal()
    {
        $key = I('param.'); //获取用户提交的数据
        $year = I('get.year', date('Y'), 'intval');

        if ($key['type'] == 'months') {

            //按月分组
            $res = M()->query("SELECT  months.month AS dates,  dj.url,  COUNT(dm.id) AS total FROM  (SELECT   '01' AS MONTH  UNION  ALL  SELECT   '02'  UNION  ALL  SELECT   '03'  UNION  ALL  SELECT   '04'  UNION  ALL  SELECT   '05'  UNION  ALL  SELECT   '06'  UNION  ALL  SELECT   '07'  UNION  ALL  SELECT   '08'  UNION  ALL  SELECT   '09'  UNION  ALL  SELECT   '10'  UNION  ALL  SELECT   '11'  UNION  ALL  SELECT   '12') AS months  CROSS JOIN   (SELECT *   FROM    ds_journals    WHERE is_use=1    ) AS dj  LEFT JOIN ds_manuscript dm   ON dm.journal_id = dj.id   AND DATE_FORMAT(dm.date_published, '%m') = months.month   AND YEAR(dm.date_published) = $year   AND dm.is_published = 1 GROUP BY months.month,  dj.url;  ");
        } else {
            //按年分组
            $res = M()->query("SELECT   years.year AS dates,   dj.url,   COUNT(dm.id) AS total FROM   (SELECT     '2016' AS YEAR   UNION   ALL   SELECT     '2017'   UNION   ALL   SELECT     '2018'   UNION   ALL   SELECT     '2019'   UNION   ALL   SELECT     '2020'   UNION   ALL   SELECT     '2021'   UNION   ALL   SELECT     '2022'   UNION   ALL   SELECT     '2023' UNION   ALL   SELECT     '2024'      ) AS years   CROSS JOIN     (SELECT       *     FROM       ds_journals     WHERE is_use = 1) AS dj   LEFT JOIN ds_manuscript dm     ON dm.journal_id = dj.id      AND YEAR(dm.date_published) = years.year     AND dm.is_published = 1 GROUP BY years.year,   dj.url;  ");
        }

        $name = array();
        $total = array();

        foreach ($res as $k => $v) {
            //记录每条的键名
            $ik = $v['url'];
            //新数据推入新数组
            array_push($total[$ik][$k] = $v['total']);

            array_push($name, $v['dates']);
        }

        //重建索引数组
        foreach ($total as $k => $value) {

            $total[$k] = array_values($value);
        }


        $data_json = array(
            'name' => array_values(array_unique($name)),
            'total' => $total,

        );
        $this->ajaxReturn($data_json);
    }

    //发布稿统计
    public function getPublishedOfManuscriptByFreeAndUnfree()
    {
        $year = I('get.year', date('Y'), 'intval');
        $journal_id = I('get.journal', 0);

        //已发布免费文章总数

        $freepublishedtotal = $this->getList('ds_manuscript', 'date_published', $year, $journal_id, 'journal_id', 'AND dn.is_published = 1 AND dn.is_free=1');

        //已发布收费文章总数

        $unfreepublishedtotal = $this->getList('ds_manuscript', 'date_published', $year, $journal_id, 'journal_id', 'AND dn.is_published = 1 AND dn.is_free=0');

        //已发布文章总数

        $publishedtotal = $this->getList('ds_manuscript', 'date_published', $year, $journal_id, 'journal_id', 'AND dn.is_published = 1');


        $msg = array(

            'freepublishedtotal' => $freepublishedtotal,
            'unfreepublishedtotal' => $unfreepublishedtotal,
            'publishedtotal' => $publishedtotal
        );

        $this->ajaxReturn($msg);
    }

    //特刊、GE、EB
    public function getIssuebookNewsAndSpecialGEEBRB()
    {
        $year = I('get.year', date('Y'), 'intval');
        $journal_id = I('get.journal', 0);

        //特刊总数

        $special_issue = $this->getList('ds_special_issue', 'date_added', $year, $journal_id, 'journal_id');

        //书刊总数

        $issuebookdtotal = $this->getList('ds_journal_issue_book', 'date_added', $year, $journal_id, 'j_id');

        //新闻总数


        $newsedtotal = $this->getList('ds_news', 'date_added', $year, $journal_id, 'journal_id');

        //EB总数
        $ebsedtotal = $this->getList('ds_customer_eb', 'addtime', $year, $journal_id, 'j_id');
        //GE总数
        $gesedtotal = $this->getList('ds_customer_ge', 'addtime', $year, $journal_id, 'j_id');
        //RB总数
        $rbsedtotal = $this->getList('ds_customer_re', 'addtime', $year, $journal_id, 'j_id');

        $msg = array(

            'special_issue' => $special_issue,

            'issuebookdtotal' => $issuebookdtotal,
            'newsedtotal' => $newsedtotal,

            'ebsedtotal' => $ebsedtotal,
            'gesedtotal' => $gesedtotal,
            'rbsedtotal' => $rbsedtotal
        );

        $this->ajaxReturn($msg);
    }

    //稿件提交
    public function getSubmitedOfManuscript()
    {
        $year = I('get.year', date('Y'), 'intval');
        $journal_id = I('get.journal', 0);
        //提交总数
        $msg = $this->getList('ds_manuscript', 'date_submit', $year, $journal_id, 'journal_id');

        $this->ajaxReturn($msg);
    }

    //发信数
    public function getEmaillogBySendmail()
    {
        $year = I('get.year', date('Y'), 'intval');
        $journal_id = I('get.journal', 0);
        //发信总数
        $msg = $this->getList('ds_email_log', 'date_added', $year, $journal_id, '');
        $this->ajaxReturn($msg);
    }
    //已发布稿文章类型统计
    public function getManuscriptInArticleType()
    {
        $msg = M('manuscript')
            ->alias('dm')
            ->field("dat.`name` AS NAME,COUNT(dm.id) AS VALUE")
            ->join('`ds_article_type` dat ON dm.`article_type_id`=dat.`id` ')
            ->where('dm.`is_published`=1 AND dat.is_use=1')
            ->group("dat.`name`")
            //->order('VALUE DESC')

            ->select();

        $this->ajaxReturn($msg);
    }

    //按年统计文章类型
    public function getManuscriptInArticleTypeofYear()
    {
        $year = I('get.year', date('Y'), 'intval');
        $journal_id = I('get.journal', 0);
        $wherejournal = '';
        if ($journal_id > 0) {
            $wherejournal = 'AND dm.journal_id=' . $journal_id;
        }

        $msg = M('manuscript')
            ->alias('dm')
            ->field("dat.`name` AS NAME,COUNT(dm.id) AS VALUE")
            ->join('`ds_article_type` dat ON dm.`article_type_id`=dat.`id` ')
            ->where('dm.`is_published`=1 ' . $wherejournal . ' AND dat.`is_use`=1 AND YEAR(dm.`date_published`) =' . $year)
            ->group("dat.`name`")
            //->order('VALUE DESC')

            ->select();
        $this->ajaxReturn($msg);
    }

    //按年按刊统计文章类型
    public function getManuscriptInArticleTypeInJournalofYear()
    {
        $year = I('get.year', date('Y'), 'intval');

        $res = M('journals')
            ->alias('dj')
            ->field("dj.`url`,dat.`name` AS name,COUNT(dm.id) AS total")
            ->join('CROSS JOIN `ds_article_type` dat ON dat.`is_use`=1  LEFT JOIN ds_manuscript dm ON dm.`journal_id`=dj.id AND  dm.`article_type_id`=dat.`id`  AND dm.`is_published`=1  AND YEAR(dm.`date_published`) =' . $year)

            ->where(' dj.`is_use`=1 ')
            ->group("dj.`url`, dat.`name`")
            //->having("total >0")
            //->order('VALUE DESC')

            ->select();


        $name = array();
        $total = array();

        foreach ($res as $k => $v) {
            //记录每条的键名
            $ik = $v['url'];
            //新数据推入新数组
            array_push($total[$ik][$k] = $v['total']);

            array_push($name, $v['name']);
        }

        //重建索引数组
        foreach ($total as $k => $value) {

            $total[$k] = array_values($value);
        }


        $data_json = array(
            'name' => array_values(array_unique($name)),
            'total' => $total,

        );
        $this->ajaxReturn($data_json);
    }

    //各个刊年度已提交数
    public function getSubmitedInJournalOfYear()
    {
        $year = I('get.year', date('Y'), 'intval');

        $msg = M('manuscript')
            ->alias('dm')
            ->field("dj.url AS NAME,COUNT(dm.id) AS VALUE")
            ->join('ds_journals dj ON dm.journal_id = dj.id')
            ->where('dj.`is_use`=1 AND YEAR(dm.`date_submit`) =' . $year)
            ->group("dj.url")
            ->order('dj.url')

            ->select();
        $this->ajaxReturn($msg);
    }

    //注册用户
    public function getCustomerOfMonth()
    {
        $year = I('get.year', date('Y'), 'intval');
        $journal_id = I('get.journal', 0);
        $msg = $this->getList('ds_customer', 'date_added', $year, $journal_id, '');

        $this->ajaxReturn($msg);
    }

    //退订邮箱
    public function getUnSubscribeOfMonth()
    {
        $year = I('get.year', date('Y'), 'intval');
        $journal_id = I('get.journal', 0);

        $msg = $this->getList('ds_subscribe_email', 'date_added', $year, $journal_id, 'AND dn.enableflag = 0');

        $this->ajaxReturn($msg);
    }


    //所有作者
    public function getAuthorOfMonth()
    {
        $year = I('get.year', date('Y'), 'intval');
        $journal_id = I('get.journal', 0);

        $msg = $this->getList('ds_ms_author', 'date_added', $year, $journal_id, '');

        $this->ajaxReturn($msg);
    }

    //发布稿年统计
    public function getPublishedOfManuscriptOfYear()
    {
        $msg = M('manuscript')
            ->alias('dm')
            ->field("YEAR(dm.date_published) AS NAME,COUNT(dm.id) AS VALUE")
            ->group("YEAR(dm.date_published) ")
            ->where("dm.is_published=1")
            ->order('YEAR(dm.date_published) ')
            ->select();
        $this->ajaxReturn($msg);
    }

    //发布稿按期刊分总量
    public function getPublishedOfManuscriptInJournalOfYear()
    {
        $msg = M('manuscript')
            ->alias('dm')
            ->field("dj.`url` AS NAME,COUNT(dm.id) AS VALUE")
            ->join('ds_journals dj ON dm.journal_id = dj.id')
            ->group("dj.`url`")
            ->where('dm.is_published = 1 AND dj.is_use = 1')
            ->order('VALUE DESC')
            ->select();
        $this->ajaxReturn($msg);
    }


    //发布稿各期刊收费数量
    public function getPublishedOfManuscriptInJournalByUnfree()
    {
        $year = I('get.year', date('Y'), 'intval');
        // $msg = M('manuscript')
        //     ->alias('dm')
        //     ->field("dj.`url` AS NAME,COUNT(dm.id) AS VALUE")
        //     ->join('ds_journals dj ON dm.journal_id = dj.id')
        //     ->group("dj.`url`")
        //     ->where('dm.is_published = 1 AND dj.is_use = 1 AND dm.is_free = 0 AND YEAR(dm.`date_published`) =' . $year)
        //     ->order('dj.`url`')
        //     ->select();

        //数量0的刊也显示
        $sql = 'SELECT   dj.`url` AS NAME,   IFNULL(subquery.VALUE, 0) AS VALUE FROM   ds_journals dj LEFT JOIN (   SELECT     dm.`journal_id`,     COUNT(dm.id) AS VALUE   FROM     ds_manuscript dm   WHERE     dm.`is_published` = 1     AND dm.`is_free` = 0     AND YEAR(dm.`date_published`) = ' . $year . '   GROUP BY     dm.`journal_id` ) subquery ON dj.id = subquery.`journal_id` WHERE   dj.`is_use` = 1;';
        $msg =    M()->query($sql);

        //sqldump();
        $this->ajaxReturn($msg);
    }
}