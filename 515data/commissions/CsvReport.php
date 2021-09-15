<?php


namespace Commissions;


class CsvReport
{
    protected $directory;

    public function __construct($directory = "csv")
    {
        $this->directory = rtrim(ltrim($directory, "/"),"/");
    }

    public function generateLink($filename, $data, $columns = [])
    {
        if(count($data) === 0) {
            $data = [
               [ 'no data' => ' ']
            ];
        }

        $filename = str_slug($filename);

        if(!is_dir(storage_path("app/public/{$this->directory}/"))) {
            mkdir(storage_path("app/public/{$this->directory}/"), 0777, true);
        }

        $fp = fopen(storage_path("app/public/{$this->directory}/") . $filename . ".csv", 'w+');

        if(count($columns) === 0) {
            $header = array_keys((array)$data[0]);
        } else {
            $header = $columns;
        }

        fputcsv($fp, $header);

        foreach ($data as $row) {
            $r = (array)$row;
            fputcsv($fp, $r, ',', '"');
        }

        fclose($fp);

        return asset("storage/{$this->directory}/" . $filename . ".csv");
    }


}