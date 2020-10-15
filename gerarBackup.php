<?php
// Conexão com o banco
function dbConnect($host, $user, $pass, $dbname)
{
    return mysqli_connect($host, $user, $pass, $dbname);
}
function dbEnd($connect)
{
    return mysqli_close($connect);
}
function Query($connect, $sql)
{
    return mysqli_query($connect, $sql);
}
function QueryM($connect, $sql)
{
    return mysqli_multi_query($connect, $sql);
}
//Pesquisa como a tabela foi criada
function getCreateTable($connect, $table)
{
    return Query($connect, "SHOW CREATE TABLE {$table}");
}

// Pega os nomes das tabelas no banco de dados 
function tableNames($connect)
{
    $table_list = Query($connect, 'SHOW TABLES');
    while ($name = mysqli_fetch_row($table_list)) $tables[] = $name[0];
    return $tables;
}

function createInstructionSQL()
{
    $result = "";
    $connect = dbConnect('localhost', 'root', '', 'portalcondomin');
    $export_tables = tableNames($connect);

    foreach ($export_tables as $table) {
        $result_column = Query($connect, "SELECT * FROM {$table}"); // Pega o resultado da tabela 
        $columm_num = mysqli_num_fields($result_column); // Verifica número de colunas na tabela
        //echo "Tabela: {$table}</br>Número de colunas: {$columm_num} </br></br>"; // Mostra número de colunas da tabela

        $result .=  "DROP TABLE IF EXISTS " . $table . ";"; //Intrução para apagar a tabela caso a mesma exista no Banco
        $create_result = getCreateTable($connect, $table); //Pesquisar como a coluna é criada
        $row_cr_col = mysqli_fetch_row($create_result);
        //echo"<pre>{$row_cr_col[1]}</pre>"; //Mostra estrutura da tabela
        $result .= "\n\n" . $row_cr_col[1] . ";\n\n";


        for ($i = 0; $i < $columm_num; $i++) {
            while ($row_tp_col = mysqli_fetch_row($result_column)) {
                $result .= "INSERT INTO {$table} VALUES("; //Criar query para inserir os dados
                for ($x = 0; $x < $columm_num; $x++) {
                    $row_tp_col[$x] = addslashes($row_tp_col[$x]); //Adiciona barras invertidas a uma string
                    $row_tp_col[$x] = str_replace("\n", "\\n", $row_tp_col[$x]); // Substitui todas as ocorrências da string \n pela \\n
                    if (isset($row_tp_col[$x])) {
                        if (!empty($row_tp_col[$x])) {
                            if (!$x) {
                                $result .= $row_tp_col[$x];
                            } else {
                                $result .= '`' . $row_tp_col[$x] . '`';
                            }
                        } else {
                            $result .= 'NULL';
                        }
                    } else {
                        $result .= 'NULL';
                    }
                    if ($x < ($columm_num - 1)) $result .= ',';
                }
                $result .= ");\n";
            }
        }
        $result .= "\n\n";
    }
    dbEnd($connect);
    return $result; // Retorna instruções sql
}
// Criar arquivo, especificar extensão no $ex
function createFile($result, $ex)
{
    //Criar o diretório de backup
    $path = 'Backup';
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        chmod($path, 0777);
    }

    //Nome do arquivo de backup
    $data = date('Y-m-d'); // Formato data com hora 'Y-m-d-h-i-s'
    $folder = "{$path}/db_backup_{$data}.{$ex}";

    if (file_exists($folder)) unlink($folder); // Se existir o arquivo apagar o mesmo

    $file = fopen($folder, 'w+');
    fwrite($file, $result);
    fclose($file);

    /*
    //Montagem do link do arquivo
    $download = "{$folder}.{$ex}";
    //Adicionar o header para download
    if (file_exists($download)) {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=\"" . basename($download) . "\";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($download));
        readfile($download);

        echo "[+] Exportado Banco";
    } else {
        echo "[X] Exportado Banco";
    }
    */
}


/*
------------------------------------------------
             Executar funções aqui 
------------------------------------------------
*/
// Exportar tabelas 

$result = createInstructionSQL();
createFile($result, 'sql');

// Importar tabelas em outro banco
$data = date('Y-m-d'); // Formato data
$folder = "Backup/db_backup_{$data}.sql";
$file = file($folder);
$tempLine = '';
$connect = dbConnect('localhost', 'root', '', 'teste');

foreach ($file as $row) {
    if (substr($row, 0, 2) == '--' || $row == '') continue;
    $tempLine .= $row;
    if (substr(trim($row), -1, 1) == ';') {
        QueryM($connect, strval($row)); // or print(mysqli_error($connect));
        $tempLine = '';
    }
}
dbEnd($connect);
