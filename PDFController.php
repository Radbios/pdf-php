<?php 

require 'vendor/autoload.php';

use Dompdf\Dompdf;

class PDFController 
{
    public static function get_file($hash)
    {
        $jsonFile = 'database.json';
        $jsonData = file_get_contents($jsonFile);
        $json = json_decode($jsonData, true);


        foreach ($json as $key => $value) {

            if(in_array($hash, $value['hash']))
            {
                return $value;
            }
        }
    }

    public static function pdf_download(string $hash)
    {
        $path = "file/";
        
        $file = PDFController::get_file($hash);
        
        $fullpath = $path . $file['filename'];
        
        if (!file_exists($fullpath)) {
            return response("Arquivo não encontrado", 404);
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($fullpath) . '"');
        header('Content-Length: ' . filesize($fullpath));
        readfile($fullpath);
    }

    public static function pdf_generate($data, $servidor_id)
    {
        $pdf = file_get_contents("pdf.html");

        $dompdf = new Dompdf(["enable_remote" => true]);
        
        $dompdf->loadHtml($pdf);

        $dompdf->setPaper('A4', 'portrait');
        
        $dompdf->render();

        $dompdf->stream("output.pdf",array('Attachment' => false));
        return;

        $pdfContent = $dompdf->output();

        $path = "file/";

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }


        // GERAR HASH PARA NOME DO ARQUIVO
        $algorithm = "sha256";

        $hash = hash($algorithm, $data);

        // SALVAR NOME E HASH NO BANCO DE DADOS

        $jsonFile = 'database.json';
        $jsonData = file_get_contents($jsonFile);
        $json = json_decode($jsonData, true);


        foreach ($json as $key => $value) {
            if($value["user_id"] == $servidor_id){
                if(!in_array($hash ,$value['hash'])){

                    array_push($json[$key]['hash'], $hash);
                    $updatedJson = json_encode($json, JSON_PRETTY_PRINT);
                    file_put_contents($jsonFile, $updatedJson);

                    $filename = $value["filename"];
                    $fullpath = $path . $filename;
                    file_put_contents($fullpath, $pdfContent);
                }
                return;
            }
        }

        $filename = $hash . ".pdf";
        $fullpath = $path . $filename;

        $newData = array(
            'user_id' => $servidor_id,
            'filename' => $filename,
            'hash' => [
                $hash
            ]
        );
        
        $json[] = $newData;
        $updatedJson = json_encode($json, JSON_PRETTY_PRINT);
        file_put_contents($jsonFile, $updatedJson);
        file_put_contents($fullpath, $pdfContent);
    }
}

PDFController::pdf_generate("sk43nddaas", 6);
// PDFController::pdf_download("bb2cb7004547a50bd70f1210da38f2e9a4ff6b23acdbf0ba5a2e1acccffb799b");
?>
