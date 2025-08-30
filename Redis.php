
<?php

namespace Obm\Controller;

use Think\Controller;
use Think\Cache\Driver\Redis;


class SearchController extends Controller
{


    //文章内容搜索页
    public function manuscriptsearch()
    {
        try{
            $redis = new Redis();
             
        $keys = $redis->keys('*');
        $numKeys = count($keys);
        $this->assign('numKeys', $numKeys);

        }// 捕获异常
        catch (\Exception $e) {
            //echo M()->getLastSql();die();
            //$msg = 'Message: ' . $e->getMessage();
            $this->assign('error', '服务不可用');
        }

        setlog();
        
        $this->display();
    }

    //刷新缓存
    public function putrefreshcache()
    {

        //清理缓存
        $redis = new Redis();
        $redis->flushAll();

        // //文章内容写入缓存
        // $where['is_published'] = 1;
        // $articles = M('manuscript')->field('id,description')->where($where)->select();
        // foreach ($articles as $article) {
        //     $key = $article['id']; // 生成唯一键
        //     $value = $article['description']; // 获取内容
        //     S($key, $value); // 写入缓存
        // }

        // 文章内容分批写入缓存
        $where['is_published'] = 1;
        $pageSize = 100; // 每次处理x条，可根据内存情况调整
        $count = M('manuscript')->where($where)->count(); // 获取总条数
        $totalPages = ceil($count / $pageSize); // 计算总页数
        
        for ($page = 1; $page <= $totalPages; $page++) {
            // 计算偏移量
            $offset = ($page - 1) * $pageSize;
            
            // 分批获取数据
            $articles = M('manuscript')
                ->field('id,description')
                ->where($where)
                ->limit($offset, $pageSize)
                ->select();
            
            if ($articles) {
                foreach ($articles as $article) {
                    $key = $article['id'];
                    $value = $article['description'];
                    S($key, $value);
                }
                
                // 释放当前批次的内存
                unset($articles);
                // 强制垃圾回收（可选）
                gc_collect_cycles();
            }
        }

        $keys = $redis->keys('*');
        $numKeys = count($keys);

        $msg = array(
            "info" => 'Cache refresh successfully',
            "numKeys" => $numKeys
        );

        $this->ajaxReturn($msg);
    }


    //查询字符
    public function getmanuscriptsearchinfo()
    {

        $key = I('param.'); //获取用户提交的数据
        $keywords = [];
        $searchinput = htmlspecialchars(urldecode(trim($key['searchinput'])));
        $redis = new Redis();
        $pattern = "*"; // 替换为您要模糊查询的字符串，使用 * 作为通配符

        // 使用 SCAN 进行增量迭代
        $iterator = null;
        $matchedPairs = [];

        do {
            // 使用 SCAN 获取匹配的键
            $keys = $redis->scan($iterator, $pattern, 100); // 每次获取 100 个键
            foreach ($keys as $key) {
                $value = $redis->get($key);
                // 判断值是否匹配模糊查询的字符串
                if (strpos($value, $searchinput) !== false) {
                    $matchedPairs[$key] = $value;
                }
            }
        } while ($iterator > 0); // 当迭代器为 0 时结束

        // 提取匹配的键
        $keywords = array_keys($matchedPairs);

        //最大取值确保性能
        $numbers = array_slice($keywords, 0, 10);

        //结果为空直接返回
        if (!$numbers) {
            $this->ajaxReturn('NaN');
        }

        $where['id'] = array('in', $numbers);

        $articles = M('manuscript')->field('id,description')->where($where)->select();

        $keywords = [];
        $searchString = $searchinput;
        foreach ($articles as $key => $value) {
            if (!$value['description']) {
                continue;
            }
            //去标签去换行
            $originalString = str_replace(array("\r", "\n"), "", strip_tags(htmlspecialchars_decode($value['description'])));

            //找到的位置前后截取x个字符
            $position = strpos($originalString, $searchString);
            if ($position !== false) {
                $start = max(0, $position - 50);
                $end = min(strlen($originalString), $position + strlen($searchString) + 50);

                $result =substr($originalString, $start, $end - $start);

                $keyword = array(
                    "description" => $result,
                    "mid" => $value['id']
                );
                array_push($keywords, $keyword);
            }
        }

        $this->ajaxReturn($keywords);
    }


    //获取关键词查询详细信息
    public function getmanuscriptinfo()
    {
        $key = I('param.'); //获取用户提交的数据
        $searchinput = htmlspecialchars(urldecode(trim($key['searchvar'])));
        $redis = new Redis();
        $pattern = "*"; // 替换为您要模糊查询的字符串，使用 * 作为通配符
        $keys = $redis->keys($pattern);
        $keywords=[];
        // 遍历键并匹配值
        foreach ($keys as $key) {
            $value = $redis->get($key);
            // 判断值是否匹配模糊查询的字符串
            if (strpos($value, $searchinput) !== false) {
                array_push($keywords, $key);
            }
        }
        if (!$keywords) {
            $this->ajaxReturn(['status' => 0]);
        }
        //最大取值确保性能
        $numbers = array_slice($keywords, 0, 20);
        $where['dm.id'] = array('in', $numbers);

        $res = M('manuscript dm')
            ->field('dm.manuscript_number ,dm.name,CONCAT("https://xxx/",dj.url,"/",dm.url_alias) AS "Url",dm.date_submit ,dm.date_accepted,dm.date_published ,dm.doi,dm.is_free')
            ->join('LEFT JOIN ds_journals dj ON dm.journal_id=dj.id ')
            ->where($where)
            ->select();
        
        $this->ajaxReturn($res);
    }
}



