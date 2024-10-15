<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait SeederFileHandler
{
    public function GenerateData($table): void
    {
        if (DB::table($table)->count() == 0) {
            $data = $this->ProcessSCV($table);

            if ($data['error']) {
                echo $data['message']."\n";
                echo $table.".csv \033[31m Cannot be seeded due to an error. File skipped."."\033[0m \n";
            } else {
                DB::beginTransaction();
                try {
                    DB::table($table)->insert($data['rows']);

                    DB::commit();
                    echo ($table.".csv \033[32m SUCCESSFULLY SEEDED.\033[0m \n");
                } catch (\Exception $exception) {
                    DB::rollBack();
                    echo ($table.".csv \033[31m FAILED TO SEED.\033[0m \n");
                    echo ("\033[31m " . $exception->getMessage() . "\033[0m \n");

                }
            }
        } else {
            echo " \033[31m Table ".$table." has data. Skipped seeding.\033[0m \n";
        }
    }

    public function ProcessSCV($table_name): array
    {
        $iterated_csv = $this->IterateCSV($table_name);

        if ($iterated_csv['error'])
            return $iterated_csv;

        $csv = $iterated_csv['rows'];

        $checked_columns = $this->checkColumns($table_name, $csv[0]);

        if ($checked_columns['error'])
            return[
                'error' => $checked_columns['error'],
                'message' => $checked_columns['message'],
                'rows' => []
            ];

        $columns = $checked_columns['columns'];
        $error = $checked_columns['error'];
        $message = $checked_columns['message'];

        $data = $this->keyValueGenerator($csv, $columns);

        return [
            'error'     => $error,
            'message'   => $message,
            'rows'      => $data,
        ];
    }

    public function IterateCSV($filename)
    {
        $csv = [];

        try {
            $csv = array_map('str_getcsv',file(base_path('database/seeders/data/'.$filename.'.csv')));
        } catch (\Exception $exception) {
            return[
                'error' => true,
                'message' => $exception->getMessage(),
                'rows' => $csv,
            ];
        }

        return [
            'error' => false,
            'message' => '',
            'rows' => $csv
        ];
    }

    public function checkColumns($table_name, $header, $exclude = [], $include=[])
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($table_name);

        $columns = array_merge($columns, $include);
        $exemptions = array_merge(['id','created_at','updated_at','deleted_at'],$exclude);
        $columns = array_diff($columns,$exemptions);

        $checked_columns = array_diff($columns, $header);

        if ($checked_columns) {
            $missing_columns = implode(', ', $checked_columns);
            return [
                'message' => 'Missing columns [ '.$missing_columns.' ] in your csv file for '.$table_name.' table. Importing aborted.',
                'columns' => $columns,
                'error' => true
            ];
        } else {
            return [
                'columns' => $columns,
                'message' => "",
                'error' => false
            ];
        }
    }

    public function keyValueGenerator($csv, $columns)
    {
        $data = [];
        for ($i = 1; $i < count($csv); $i++) {
            $row = [];
            for ($j = 0; $j < count($csv[$i]); $j++) {
                if (in_array($csv[0][$j], $columns)) {
                    $value = (trim($csv[$i][$j]) == "" || $csv[$i][$j] == null)? null : $csv[$i][$j];
                    $row = array_merge($row, [$csv[0][$j] => $value]);
                }
            }

            $date = date('Y-m-d H:i:s');
            $row = array_merge($row, ['created_at' => $date, 'updated_at' => $date]);
            $data[] = $row;
        }

        return $data;
    }
}
