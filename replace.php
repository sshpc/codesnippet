<?php

//正文
function contentreplace($textStr, $picturehtml)
{

    //去除 Figure S 1A 等已有的错误链接
    $patterns = array(

        '/<a\shref="#figure(.*?)">Figure(.*?)<\/a>/',
        '/<a\shref="#table(.*?)">Table(.*?)<\/a>/',

    );
    $replacements = array(
        'Figure $2',
        'Table $2',
    );

    $textStr = preg_replace($patterns, $replacements, $textStr);

    //Table、Figure锚点链接替换
    $patterns = array(
        // Figure 1 ,Figure 1(a), Figure 1 (a), (Figure 1, Figure 1),Figure 1,[Figure 1].
        '/\s([\(\[])?Figure\s+(\d+)(\w)?(\s?\(\w\)?\(-\)?)?([\)\[])?(,)?(\.)?/',

        //Table 1 同figure
        '/\s([\(\[])?Table\s+(\d+)(\w)?(\s?\(\w\)?)?([\)\[])?(,)?(\.)?/',

        // Figure 开头替换
        '/;">Figure\s+(\d+)(\w)?(\s?\(\w\)?\(-\)?)?\s/',

        //Table 开头替换
        '/;">Table\s+(\d+)\s/',

        // Figure (1)
        '/Figure\s+\((\d+)\)/',

    );
    $replacements = array(
        ' $1<a href="#figure0$2">Figure $2$3$4</a>$5$6$7',

        ' $1<a href="#table0$2">Table $2$3$4</a>$5$6$7',

        ';"><a href="#figure0$1">Figure $1$2$3</a> ',

        ';"><a href="#table0$1">Table $1</a> ',

        '<a href="#figure0$1">Figure ($1)</a>',

    );

    $textStr = preg_replace($patterns, $replacements, $textStr);

    //Figures 替换
    $patterns = array(

        '/\s([\(\[])?Figures\s+(\d+)(\w)?(\s?\(\w\)?)?(\sand\s)?(\s?\(\w\)?)?([\)\[])?(,)?(\.)?/'

    );
    $replacements = array(
        ' $1<a href="#figure0$2">Figures $2$3$4$5$6</a>$7$8$9',

    );
    $textStr = preg_replace($patterns, $replacements, $textStr);

    //替换3级标题注解
    $reg = '(<p align="JUSTIFY" style="text-indent: 0.2in; line-height: 0.22in;">)\d+\.\d+\.\d+\s(.*)(<\/p>)';
    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $v) {
            array_push($waitArr, $v);

            $vi = str_replace(array('<p align="JUSTIFY" style="text-indent: 0.2in; line-height: 0.22in;">', '</p>'), '', $v);

            $htmlStr = '<h3 class="western" style="margin-top: 0.17in; line-height: 0.22in;font-size:14px;font-weight:normal;" align="JUSTIFY">' . $vi . '</h3>';
            array_push($replaceArr, $htmlStr);
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }

    //where去除首行缩进
    $patterns = '/style="text-indent: 0\.2in; line-height: 0\.22in;">where/';
    $replacements = 'style="text-indent: 0in; line-height: 0.22in;">where';
    $textStr = preg_replace($patterns, $replacements, $textStr);

    //批量去掉表格宽度
    $patterns = '/<td width="[\.\d]*%">/';
    $replacements = '<td >';
    $textStr = preg_replace($patterns, $replacements, $textStr);

    //模态框表格标题补全
    $regin = '<center>.*\n<table.*id="table.*>';
    preg_match_all("/$regin/", $textStr, $resultin);
    if ($resultin) {
        $waitArr = array();
        $replaceArr = array();
        $regout = '<p.* style="margin: 0\.17in 0\.39in; line-height: 0\.22in"><a id="table0.*<\/p>';
        preg_match_all("/$regout/", $textStr, $resultout);
        foreach ($resultin[0] as $k => $v) {
            array_push($waitArr, $v);
            $vi = str_replace('<center>&nbsp;', '<center>' . $resultout[0][$k], $v);
            array_push($replaceArr, $vi);
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }



    //图片对齐方式
    $reg = '<p align="center" style="margin: 0\.17in 0\.39in; line-height: 0\.22in;"><b>Figure(.*)';
    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $v) {
            array_push($waitArr, $v);
            if (strlen(strip_tags($v)) > 90) {
                $v = str_replace('align="center"', 'align="justify"', $v);
            }
            array_push($replaceArr, $v);
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }
    //表格对齐方式
    $reg = '<p align="center" style="margin: 0\.17in 0\.39in; line-height: 0\.22in"><a id="table(.*)';
    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $v) {
            array_push($waitArr, $v);
            if (strlen(strip_tags($v)) > 90) {
                $v = str_replace('align="center"', 'align="justify"', $v);
            }
            array_push($replaceArr, $v);
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }

    //表格大小调整
    $reg = '<table(.*)>';
    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $v) {
            array_push($waitArr, $v);
            $v = str_replace('style="', 'style=" width:98% !important; ', $v);
            array_push($replaceArr, $v);
        }

        //添加全局表格样式
        $textStr = '<style type="text/css">table tr:last-child {border-bottom: 2px solid #00000a !important;}</style> ' . $textStr;
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }

    //补ol ul列表样式
    if (strpos($textStr, "<ol>") !== false || strpos($textStr, "<ul>") !== false) {

        $textStr = str_replace(['<ol>', '<ul>'], ['<ol class="ol1">', '<ul class="ul1">'], $textStr);
        $textStr = '<style type="text/css"> .ul1 li { list-style-type:disc !important; margin-left:0.4in!important; line-height: 0.22in!important;text-align: justify} .ol1 li { list-style-type:decimal !important; margin-left:0.4in!important; line-height: 0.22in!important;text-align: justify}</style>' . PHP_EOL . $textStr;
    }

    //替换脚注
    $reg = '<a\shref="#_ftn.*<\/a>';
    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $k => $v) {
            array_push($waitArr, $v);
            $v = "<sup><a class='tippyShow' data-tippy-arrow='true' data-tippy-content='####' data-tippy-interactive='true'  data-tippy-theme='light-border'  style='cursor:pointer'>" . ($k + 1) . "</a></sup>";
            array_push($replaceArr, $v);
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }

    //替换图片url
    if ($picturehtml) {
        $picturehtml = json_decode($picturehtml, true);
        //批量url替换
        $reg = 'name="figure0(\d)+" style="display: block;height:82px;margin-top:-82px;"><\/a><img alt="Click to view original image" class="imgShow img-responsive" src=""';
        preg_match_all("/$reg/", $textStr, $result);
        if ($result) {
            $waitArr = array();
            $replaceArr = array();
            foreach ($result[0] as $k => $v) {
                array_push($waitArr, $v);
                $i = "style='max-width: 400px;' src='" . $picturehtml[$k]['url'] . "'";
                $v = str_replace('src=""', $i, $v);
                array_push($replaceArr, $v);
            }
            $textStr = str_replace($waitArr, $replaceArr, $textStr);
        }
    }

    //替换reg上标

    $textStr = str_replace('&reg;', '<sup>&reg;</sup>', $textStr);

    //正文超链接添加
    //排除标签内的
    $reg = "[^'\"]https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9]{1,6}\b([-a-zA-Z0-9!@:%_\+.~#?&\/\/=]*)";
    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $k => $v) {
            array_push($waitArr, $v);
            $strhttp = substr($v, 1);
            $i = substr($v, 0, 1) . "<a href=\"" . $strhttp . "\">" . $strhttp . "</a>";
            array_push($replaceArr, $i);
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }


    //修改附件提示样式

    $textStr = str_replace('<p align="JUSTIFY" style="text-indent: 0.2in; line-height: 0.22in;">The following additional materials are uploaded at the page of this paper.</p>', '<p align="JUSTIFY" style="text-indent: 0.2in;margin: 0.17in 0in; line-height: 0.22in;">The following additional materials are uploaded at the page of this paper.</p>', $textStr);


    return $textStr;
}

//文献替换错误a链接
function referreplace($textStr)
{

    $reg = '<a\s(name.*?)>(.*?)(<\/a>)';
    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $v) {
            array_push($waitArr, $v);

            preg_match_all("/<a\s(name=.*?)>/", $v, $res);

            $htmlStr = str_replace(array($res[0][0], '</a>'), '', $v);

            array_push($replaceArr, $htmlStr);
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }


    $reg = '/<a\b[^>]*>(?!.*http).*<\/a>/';
    preg_match_all($reg, $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $v) {
            array_push($waitArr, $v);

            preg_match_all("/<a\b[^>]*>/", $v, $res);

            $htmlStr = str_replace(array($res[0][0], '</a>'), '', $v);

            array_push($replaceArr, $htmlStr);
        }

        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }
    //如果一行中有多个匹配结果，目前只能匹配最后一个，再过滤一遍
    $reg = '<a\b[^>]*>(?!.*http).*<\/a>';
    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $v) {
            array_push($waitArr, $v);

            preg_match_all("/<a\b[^>]*>/", $v, $res);

            $htmlStr = str_replace(array($res[0][0], '</a>'), '', $v);

            array_push($replaceArr, $htmlStr);
        }

        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }



    return  $textStr;
}

//通用替换
function commonreplace($textStr, $reg, $replaced, $change)
{

    preg_match_all("/$reg/", $textStr, $result);
    if ($result) {
        $waitArr = array();
        $replaceArr = array();
        foreach ($result[0] as $v) {
            array_push($waitArr, $v);
            $htmlStr = str_replace($replaced, $change, $v);
            array_push($replaceArr, $htmlStr);
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }
    return  $textStr;
}

//基础信息
function baseinforeplace($textStr, $basehtml)
{


    //替换作者机构标和邮箱
    if ($basehtml) {
        //替换多余的sup
        $pattern = '<\/sup>\s*?<sup>';
        $basehtml = preg_replace("/$pattern/", '', $basehtml);

        //替换作者机构标
        $regin = '.....&nbsp;<sup>(.*?)<\/sup>';
        preg_match_all("/$regin/", $textStr, $resultin);

        if ($resultin) {
            $waitArr = array();
            $replaceArr = array();
            $regout = '<sup>(.*?)<\/sup>';
            preg_match_all("/$regout/", $basehtml, $resultout);
            foreach ($resultin[0] as $k => $v) {
                array_push($waitArr, $v);
                $vi = str_replace('1,*', strip_tags($resultout[0][$k]), $v);
                array_push($replaceArr, $vi);
            }
            $textStr = str_replace($waitArr, $replaceArr, $textStr);
        }


        //替换作者邮箱
        $regin = '.....&nbsp;<sup>(.*?)<\/sup><a\shref="mailto:\*@\*\.\*"';
        preg_match_all("/$regin/", $textStr, $resultin);
        if ($resultin) {
            $waitArr = array();
            $replaceArr = array();
            $regout = 'mailto:[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}';
            preg_match_all("/$regout/", $basehtml, $resultout);
            foreach ($resultin[0] as $k => $v) {
                array_push($waitArr, $v);
                $vi = str_replace('mailto:*@*.*', strip_tags($resultout[0][$k]), $v);
                array_push($replaceArr, $vi);
            }
            $textStr = str_replace($waitArr, $replaceArr, $textStr);
        }
    }


    //替换页码(主站已改)
    // $reg = ' doi:10.21926[\d\.\/\w]*\d';
    // preg_match("/$reg/", $textStr, $doi);

    // if ($doi) {
    //     $doilast = substr($doi[0], -3);

    //     $finalreg = ' ' . $doilast;

    //     if (preg_match("/页码/", $textStr)) {
    //         $textStr = preg_filter("/页码/", $finalreg, $textStr);
    //     }

    //     if (preg_match("/strong>;/", $textStr)) {
    //         $textStr = preg_filter("/strong>;/", 'strong>; ', $textStr);
    //     }
    // }


    //替换作者缩写 （Leona A. Holmberg, Michael Linenberger  中间有点的存在bug）
    $reg = 'Recommended citation:&nbsp;<\/strong>([^\.]*)\.\s';

    preg_match_all("/$reg/", $textStr, $resultRecommended);

    if ($resultRecommended) {

        $waitArr = 'Recommended citation:&nbsp;</strong>';

        $result = trim(str_replace($waitArr, '', $resultRecommended[1][0]));

        $resultarray = explode(",", $result);

        $waitArr = array();

        foreach ($resultarray as $v) {

            $words = explode(" ", trim($v));

            $first_letter = substr($words[0], 0, 1);

            unset($words[0]);

            array_push($words, $first_letter);

            $words = implode(' ', $words);

            array_push($waitArr, $words);
        }
        $replace = implode(', ', $waitArr);

        $replace = str_replace($result, $replace, $resultRecommended[0][0]);
        $textStr = str_replace($resultRecommended[0][0], $replace, $textStr);
    }




    //替换机构样式

    $reg = '<p\sstyle=\"text-indent:\s-14px;margin:0\sauto;padding-left:14px;\">.*<\/p>';
    preg_match_all("/$reg/", $textStr, $result);

    if ($result) {

        // // 删除 <ol> 标签
        // $textStr = preg_replace('/<ol[^>]*>/', '', $textStr);
        // $textStr = preg_replace('/<\/ol>/', '', $textStr);

        // // 删除 <li> 标签
        // $textStr = preg_replace('/<li[^>]*>/', '', $textStr);
        // $textStr = preg_replace('/<\/li>/', '', $textStr);


        $waitArr = array();
        $replaceArr = array();
        $htmlStr = '';

        $resultcount = count($result[0]);

        foreach ($result[0] as $k => $v) {

            array_push($waitArr, $v);

            $vv = strip_tags($v);

            if ($k == 0) {

                if (count($result[0]) == 1) {

                    $htmlStr .= '<ol style="margin-left:0px;"><li style="list-style-type:none;"><p style="text-align: justify;">' . $vv . '</p></li></ol>';
                } else {
                    $htmlStr .= '<ol style="margin-left:15px;"><li style="list-style-type:decimal;"><p style="text-align: justify;">' . $vv . '</p></li>';
                }
            } else {

                if ($k == $resultcount - 1) {
                    $htmlStr = '<li style="list-style-type:decimal;"><p style="text-align: justify;">' . $vv . '</p></li>';
                    $htmlStr .= '</ol>';
                } else {
                    $htmlStr = '<li style="list-style-type:decimal;"><p style="text-align: justify;">' . $vv . '</p></li>';
                }
            }

            array_push($replaceArr, $htmlStr);
            $htmlStr = '';
        }
        $textStr = str_replace($waitArr, $replaceArr, $textStr);
    }


    //替换&Dagger;行样式
    $reg = '<p\sstyle="text-align:\sjustify;">&';

    $textStr = preg_replace("/$reg/", '<p style="text-align: justify;margin:20px 0px;margin-left:0.2in;text-indent: -0.2in">&', $textStr);

    //替掉机构里文本序号<li>1.

    // $reg = '<li\sstyle="list-style-type:decimal;">\d\.\s.*<\/li>';
    // preg_match_all("/$reg/", $textStr, $result);

    // if ($result) {
    //     $waitArr = array();
    //     $replaceArr = array();
    //     $htmlStr = '';

    //     foreach ($result[0] as $k => $v) {

    //         array_push($waitArr, $v);

    //         preg_match_all("/\d\.\s/", $v, $res);

    //         if ($res) {
    //             $htmlStr = str_replace($res[0][0], '', $v);
    //         }

    //         array_push($replaceArr, $htmlStr);
    //         $htmlStr = '';
    //     }
    //     $textStr = str_replace($waitArr, $replaceArr, $textStr);
    // }

      //题目有符号的去掉文中双标点 ?. 为 .
      $reg = '\?\.';

      $textStr = preg_replace("/$reg/", '?', $textStr);
  

    return  $textStr;
}













    //基础信息调用转换  241021
    public function invokeConversion(){

        try{
          
        

        $id = I('get.id',0,'intval');
        $msInfo=M('manuscript')->where(['id' => $id])->find();
        $msInfo['date_publishedPrimitive']=$msInfo['date_published'];
        $all_article_author=M('ms_author')->where(['ms_id' => $id])->select();

        $specialInfo=M('special_issue')->where(['id' => $msInfo['special_issue_id']])->find();

        //基础信息调用  2410
        $Basicinformation='';

        $journalinfo=M('journals')->where(['id' => $msInfo['journal_id']])->find();
    
        $issueinfo=M('journals_issue')->where(['id' => $msInfo['issue_id']])->find();

        //获取作者

        //若上标全1表示只有一条机构上标不显示
        $allSupOne = true;
        foreach ($all_article_author as $key => $value) {
            if($value['sup'] !== '1'){
                $allSupOne = false;
                break;
            }
        }
         
        if ($allSupOne) {
            foreach ($all_article_author as $key => $value) {
                $all_article_author[$key]['sup'] = '';
            }
        }


        $miauthor='<p style="margin-bottom: 20px;">';

        $authorinitials='';
        $auCorrespondence='';//通讯作者
        foreach ($all_article_author as $key => $value) {
            
            //"\u00c1ngela"
            $localfirstname=\json_decode('"'.$value['firstname'].'"');
            //Ángela
            
            //作者姓名
            $author_name = $localfirstname . ' ' .$value['lastname'] ;
            
                //组合作者缩写字符串下面用 姓+名首字母 
                $firstnamewords = explode(' ', $localfirstname);
                
                $initialLetters = [];
                    foreach ($firstnamewords as $word) {
                        
                        if (!empty($word)) {
                            //$initialLetters[] = strtoupper(substr($word, 0, 1));
                            $initialLetters[] = mb_strtoupper(mb_substr($word, 0, 1));
                            
                        }
                    }
                    
                $authorinitials.=$value['lastname'].' '.implode('', $initialLetters);
                //判断是否末尾添加逗号
                if($key!=count($all_article_author)-1){
                    $authorinitials.=', ';
                }


            //拼接上标
            if($value['sup']){
                $ausup=$value['sup'];
            }else{
                $ausup='';
            }
            //检查是否有特殊符号
            if($value['symbol']){
                
                $ausymbolarr=M('manuscript_author_organization_symbol maos')->field('mas.symbols_htmlentity as name')->join('ds_manuscript_author_symbols mas on mas.id = maos.sort')->where(['maos.ms_id' => $msInfo['id']])->order('maos.id asc')->find();
                //判断是否加逗号
                if($ausup!=''){
                    $ausup.=','.$ausymbolarr['name'];
                }else{
                    $ausup=$ausymbolarr['name'];
                }
                
            }

            //检查是否通讯作者
            if($value['correspond']=='1'){
                if($ausup!=''){
                    $aucorrespond=',*';
                    $ausup.=$aucorrespond;

                }else{
                    $ausup.='*';
                }

                //给下面描述用 <p style="margin:20px 0px;">*&nbsp;<strong>Correspondence: </strong>
                
                $auCorrespondence.='<span></span>'.$author_name.'<a href="mailto:'.$value['email'].'" target="_blank" style="color: rgb(111, 148, 38);"><img alt="" height="19" src="/image/data/current_issue/email_icon.png" width="24" /></a>';
                //检查orcid
                if($value['orcid']){
                    $auCorrespondence.='&nbsp;<a href="https://orcid.org/'.$value['orcid'].'" style="text-decoration: none;"><img alt="ORCID logo" height="16" src="https://info.orcid.org/wp-content/uploads/2019/11/orcid_16x16.png" width="16" /></a>';
                }
                
            }

            //拼接上标
            $miauthor.=$author_name.' <sup>'.$ausup.'</sup>';
            //拼接邮箱
            if($value['email']){
                $miauthor.='<a href="mailto:'.$value['email'].'" target="_blank" style="color: rgb(111, 148, 38);"><img alt="" height="19" src="/image/data/current_issue/email_icon.png" width="24" /></a>';
            }
            //检查orcid
            if($value['orcid']){
                $miauthor.='&nbsp;<a href="https://orcid.org/'.$value['orcid'].'" style="text-decoration: none;"><img alt="ORCID logo" height="16" src="https://info.orcid.org/wp-content/uploads/2019/11/orcid_16x16.png" width="16" /></a>';
            }

            //判断是否末尾添加逗号
            if($key!=count($all_article_author)-1){
                $miauthor.=', ';
            }else{
                $miauthor.='</p>';
            }
        }
        
        $Basicinformation.=$miauthor;

        
        
        //获取机构
        $organizationinfo=M('manuscript_author_organization')->field('name')->where(['ms_id' => $msInfo['id'],'enbleflag' => '1'])->order('sort asc')->select();
        

        //机构字符串
        $organizationstr='';
        //如果机构只有1条
        if(count($organizationinfo)==1){
            $organizationstr.='<ol style="margin-left:0px;"><li style="list-style-type:none;"><p style="text-align: justify;">'.$organizationinfo[0]['name'].'</p></li></ol>';
        }else{
            $organizationstr.='<ol style="margin-left:15px;">';
            foreach ($organizationinfo as $key => $value) {
                $organizationstr.='<li style="list-style-type:decimal;"><p style="text-align: justify;">'.$value['name'].'</p></li>';
            }
            $organizationstr.='</ol>';
            
        }
        $Basicinformation.=$organizationstr;
        

        //特殊符号
        

        $ausymbolname=M('manuscript_author_organization_symbol maos')->field('maos.name,mas.symbols_htmlentity as symbolname')->join('ds_manuscript_author_symbols mas on mas.id = maos.sort')->where(['maos.ms_id' => $msInfo['id']])->order('maos.id asc')->select();

        
        if($ausymbolname){
            foreach ($ausymbolname as $key => $value) {
                $Basicinformation.='<p style="margin:20px 0px;">'.$value['symbolname'].' '.$value['name'].'</p>';
            }
        }
        

        //通讯作者
        
        $parts = explode('<span></span>', $auCorrespondence);

        $filteredParts = array_filter($parts, function($part) {
            return trim($part)!== '';
        });
        
        if (count($filteredParts) <= 1) {
            //保持原样
        } else {
            //若有多个通讯
            if (count($filteredParts) === 2) {
                $auCorrespondence= $filteredParts[1]. ' and '. $filteredParts[2];
            } else {
                $auCorrespondence= implode(', ', array_slice($filteredParts, 0, -1)). ' and '. end($filteredParts);
                
            }
        }

        $Basicinformation.='<p style="margin:20px 0px;">*&nbsp;<strong>Correspondence: </strong>'.$auCorrespondence.'</p>';

        //Academic Editor 最后做决定的

        $lastdecisionEditor = M('manuscript_decision md')->field('dc.firstname,dc.lastname')->join('LEFT JOIN ds_customer dc ON dc.id = md.c_id')->where(['md.m_id' => $msInfo['id']])->order('md.date_added desc')->find();
        if($lastdecisionEditor){
            $Basicinformation.='<p style="margin-bottom: 20px;"><b>Acadeor:</b>&nbsp;'.$lastdecisionEditor['firstname'].' '.$lastdecisionEditor['lastname'].'</p>';
        }

        

        //特刊
        if($specialInfo){
            //是否合集
            if($specialInfo['is_collection']==0){
                $Basicinformation.='<p style="margin-bottom: 20px;"><strong>Special Issue:</strong>&nbsp;<a href="'.SET_WEB_DOMAIN_FOR_WEB.'/journals/'.$journalinfo['url'].'/'.$journalinfo['url'].'-special-issues/'.$specialInfo['url_alias'].'">'.$specialInfo['name'].'</a></p>';
            }else{
                $Basicinformation.='<p style="margin-bottom: 20px;"><strong>Collection:</strong>&nbsp;<a href="'.SET_WEB_DOMAIN_FOR_WEB.'/journals/'.$journalinfo['url'].'/'.$journalinfo['url'].'-collection/'.$specialInfo['url_alias'].'">'.$specialInfo['name'].'</a></p>';
            }
            
        }
        

        //Received Accepted  Published
        
        $Basicinformation.='<p style="margin-bottom: 20px;margin-top:20px;"><strong>Received:</strong>&nbsp;'.$this->timeFormat($msInfo['date_submit']).' |&nbsp;<strong>Accepted:</strong>&nbsp;'.$this->timeFormat($msInfo['date_accepted']).' |&nbsp;<strong>Published:&nbsp;</strong>'.$this->timeFormat($msInfo['date_publishedPrimitive']).'</p>';

        //Recommended citation

        $Recommendedjournaltitle='';
        if($journalinfo['url']=='aeer' || $journalinfo['url']=='rpse'){
            $Recommendedjournaltitle=$journalinfo['shortjournal'];
        }else{
            $Recommendedjournaltitle=$journalinfo['en_title'];
        }

        $Basicinformation.='<p style="margin-bottom:20px;">'.$Recommendedjournaltitle.' <strong>'.date('Y', strtotime($msInfo['date_publishedPrimitive'])).'</strong>, Volume '.$issueinfo['volume'].', Issue '.$issueinfo['issue_title'].', doi:<a href="http://dx.doi.org/'.$msInfo['doi'].'" target="_blank">'.$msInfo['doi'].'</a></p>';

        $Basicinformation.='<p style="margin-bottom: 20px;"><strong>Rec:&nbsp;</strong>'.$authorinitials.'. '.html_entity_decode($msInfo['title']).'. <em>'.$Recommendedjournaltitle.'</em> <strong>'.date('Y', strtotime($msInfo['date_publishedPrimitive'])).'</strong>; '.$issueinfo['volume'].'('.$issueinfo['issue_title'].'): '.substr($msInfo['doi'], -3).'; doi:'.$msInfo['doi'].'.</p>';

        $Basicinformation.='<p style="text-align:justify">&copy; '.date('Y', strtotime($msInfo['date_publishedPrimitive'])).' bed.</p>';

        //Abstract Keywords
        if($msInfo['abstract']){
        $description='<div style="border: 2px solid rgb(56, 93, 138); border-radius: 25px; margin: 20px 0px;"><h3 align="JUSTIFY" class="western" style="margin: 10px 0.2in; line-height: 16pt;"><b>Abstract</b></h3><p align="JUSTIFY" style="margin:0in 0.2in; line-height: 0.22in">'.html_entity_decode($msInfo['abstract']).'</p><h3 align="JUSTIFY" class="western" style="margin: 10px 0.2in; line-height: 0.22in;"><b>Keywords </b></h3><p align="JUSTIFY" style="margin-left: 0.2in; margin-right: 0.2in; margin-bottom: 0.17in; line-height: 0.22in;">'.html_entity_decode($msInfo['keyword']).'</p></div>';
        }

        $msg = array(
            'status' => 1,
            'msg' => 'success',
            'common_description' => $Basicinformation,
            'description' => $description

        );
        
    }catch(Exception $e){
        $msg = array(
           'status' => 0,
           'msg' =>'error',
            'common_description' => '',
            'description' => ''

        );
    }

        
        $this->ajaxReturn($msg);
        
    }

    //稿件展示时间格式化
    private function timeFormat($time){
        $date = date('F j, Y', strtotime($time));
            $parts = explode(' ', $date);
            $dayNumber = (int)$parts[1];
            $day = $dayNumber < 10? '0'.$dayNumber : $dayNumber;
            return $parts[0].' '.$day.', '.$parts[2];
            
    }