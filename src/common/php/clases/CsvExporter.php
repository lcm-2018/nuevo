<?php

namespace Src\Common\Php\Clases;


class CsvExporter
{
    private $filename;
    private $delimiter;
    private $headers = [];
    private $exampleRow = [];
    private $data = [];

    public function __construct($filename = 'archivo.csv', $delimiter = ';')
    {
        $this->filename = $filename;
        $this->delimiter = $delimiter;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function setExampleRow(array $row)
    {
        $this->exampleRow = $row;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function download()
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $this->filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if (!empty($this->headers)) {
            fputcsv($output, $this->headers, $this->delimiter);
        }

        if (!empty($this->exampleRow)) {
            fputcsv($output, $this->exampleRow, $this->delimiter);
        }

        foreach ($this->data as $row) {
            fputcsv($output, $row, $this->delimiter);
        }

        fclose($output);
        exit;
    }
}
