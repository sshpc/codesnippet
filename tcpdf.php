<?php
namespace User\Controller;

vendor("TCPDF.tcpdf");

use \Common\Controller\FrontuserController;
use TCPDF;
use Think\View;

// 创建PDF
            $pdf = new TCPDF('L', PDF_UNIT, 'A5', true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetAutoPageBreak(TRUE, 0);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', '', 13);


            //表头
            $pdf->Image($img_icmico, 165, 8, 35);

            $pdf->SetFontSize(6);
            $tophtml='';
            $pdf->writeHTMLCell(160, 10, 10, 12, $tophtml, 0, 0, false, true, 'L');

            //标题
            $pdf->SetFontSize(13);
            $pdf->writeHTMLCell(160, 16, 20, 39, '<b>Certificate of Acceptance</b>', 0, 0, false, true, 'C');

            // 写入内容开头
            $pdf->SetFontSize(9);
            $pdf->writeHTMLCell(170, 16, 25, 49, '<p  <i>' . $journal['en_title'] . '</i>:</p>', 0, 0, false, true, 'L');

            $ic = intval(strlen($center_html) / 30-20);

            
            $center_html = str_replace(['30%', '70%','10.8px','18px'], ['30'-($ic/1.5).'%', '70'+($ic/1.5).'%','10.8'-($ic/6).'px','18'-($ic/6).'px'], $center_html);
            // 写入内容
            $pdf->SetFontSize(9);
            $pdf->writeHTMLCell(170+($ic/5), 16, 35-($ic*1), 59, $center_html, 0, 0, false, true, 'L');

            // 写入内容结尾
            $pdf->SetFontSize(9);
            $pdf->writeHTMLCell(170, 16, 25, 105, '<p>It is now in press and will be published online soon. Congratulations to the author!</p>', 0, 0, false, true, 'L');
            

            // 写入图片和出版者信息
            $pdf->Image($img_icmEic, 45, 110, 25);
            $pdf->Image($img_icmkelley, 140, 115, 25);

            //底部
            $pdf->SetFontSize(9);
            $pdf->writeHTMLCell(160, 16, 2, 125, '<table style="text-align: center;"><tr><td  width="70%"</table>', 0, 0, false, true, 'L');

            $pdf->Output(sprintf('%s.pdf', date('YmdHis')));