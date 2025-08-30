<?php
/**
     * 批量更新
     * 对有问题的hindex重新查询  240718
     * @internal
     */
    public function multi($ids = "")
    {

        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {

            //仅允许勾选一个
            $parts = explode(',', $ids);
            if (count($parts) > 1) {
                $this->error('仅支持勾选一个');
            }

            if ($this->request->has('params')) {

                Db::startTrans();

                try {
                    //漏查的数量
                    $errcount=0;

                    $list = Db::table('fa_search_task')->where('id', $ids)->find();
                    file_put_contents('phpfiledump.txt', json_encode($list['task_no']) . PHP_EOL, FILE_APPEND);

                    $where['task_no']=$list['task_no'];
                    $where['results']=0;
                    $errcount=Db::table('fa_search_author')->where($where)->count();

                    $wheretask['task_no']=$list['task_no'];
                    $datetask['total']=$list['total']-$errcount;
                    $datetask['status']='normal';
                    Db::table('fa_search_task')->where($wheretask)->update($datetask);

                    $date['status']='hidden';
                    Db::table('fa_search_author')->where($where)->update($date);
                    //$this->success($list['task_no']);
                    // $time=date('Y-m-d H:i:s');
                    // $data = ['purger_id' => $list[0]['email_purger_id'], 'type' => $values['is_send'],'time'=> $time];
                    // Db::table('fa_email_queue_history')->insert($data);

                    Db::commit();
                } catch (PDOException $e) {
                    $this->success(1234);
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $this->success(12345);
                    Db::rollback();
                    $this->error($e->getMessage());
                }

                $this->success('成功更新了 '.$errcount.' 条数据');
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
        // 管理员禁止批量操作
        //$this->error('123');
    }







    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post('row/a');
            $filename = sprintf('%spublic/%s', ROOT_PATH, $post['filename']);
            $reader = IOFactory::createReaderForFile($filename);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filename);
            $sheet = $spreadsheet->getActiveSheet();


            //查出ALL Journlas id 用于全拉黑  240614

            $ALLjournal_id = Db::table("fa_category")->field('id')->where(['pid' => 0, 'status' => 'normal','name' => 'ALL Journlas'])->find();
            $ALLjournal_id=$ALLjournal_id['id'];
            $ALLjournal_sid = Db::table("fa_category")->field('id')->where(['status' => 'normal','name' => 'none'])->find();
            $ALLjournal_sid=$ALLjournal_sid['id'];

            $journal_id = $post['journal_id'];

            if ($journal_id == $ALLjournal_id) {
                //判断特刊是那个
                if ($post['special_issue_id'] == $ALLjournal_sid) {
                    $special_issue_id = 0;
                } else {
                    $special_issue_id = isset($post['special_issue_id']) ? intval($post['special_issue_id']) : 0;
                }

                foreach ($sheet->getRowIterator(2) as $row) {

                    $status = strtolower(trim($sheet->getCell('B' . $row->getRowIndex())->getValue()));
                    $values = strtolower(trim($sheet->getCell('A' . $row->getRowIndex())->getValue()));
                    $xmails = explode(';', $values);
                    foreach ($xmails as $xmail) {
                        $xmail = trim($xmail);
                        if ($this->model->get(['journal_id' => $ALLjournal_id, 'special_issue_id' => $special_issue_id, 'xmail' => $xmail])) {
                            continue;
                        }
                        $this->model->create(array(
                            'admin_id'         => $this->auth->id,
                            'journal_id'       => $ALLjournal_id,
                            'special_issue_id' => $special_issue_id,
                            'xmail'            => $xmail,
                            'filename'         => $post['filename'],
                            'status'           => $status,
                            'is_fullblack'     => 1
                        ));
                    }
                }
            } else {
                $special_issue_id = isset($post['special_issue_id']) ? intval($post['special_issue_id']) : 0;

                foreach ($sheet->getRowIterator(2) as $row) {
                    $status = strtolower(trim($sheet->getCell('B' . $row->getRowIndex())->getValue()));
                    $values = strtolower(trim($sheet->getCell('A' . $row->getRowIndex())->getValue()));
                    $xmails = explode(';', $values);
                    foreach ($xmails as $xmail) {
                        $xmail = trim($xmail);
                        if ($this->model->get(['journal_id' => $journal_id, 'special_issue_id' => $special_issue_id, 'xmail' => $xmail])) {
                            continue;
                        }
                        $this->model->create(array(
                            'admin_id'         => $this->auth->id,
                            'journal_id'       => $journal_id,
                            'special_issue_id' => $special_issue_id,
                            'xmail'            => $xmail,
                            'filename'         => $post['filename'],
                            'status'           => $status,
                            'is_fullblack'     => 0
                        ));
                    }
                }
            }

            
            $this->success();
        }
        return $this->view->fetch();
    }