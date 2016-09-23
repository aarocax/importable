<?php

$time_start = microtime(true);


$hostname = 'localhost';
$database = 'database';
$username = 'username';
$username = 'username';

$registrosCoincidentes = 0;
$registrosGuardados = 0;
$registrosDescartados = 0;

$csv_filename = 'exportfile_'.date('Y-m-d H:m:s').'.csv';
$csv_export = '';

$mysqli = new mysqli($hostname, $username, $password, $database);

if ($mysqli->connect_errno) {
  printf("Connect failed: %s\n", $mysqli->connect_error);
  exit();
}

$sql = "SELECT count(*) from users";
$records = $mysqli->query($sql);
$count=$records->fetch_row();
print "<br>"."Registros en la tabla users: $count[0]";

$sql = "SELECT count(*) from bbvaopen_crm_register_people";
$records = $mysqli->query($sql);
$count=$records->fetch_row();
print "<br>"."Registros en la tabla bbvaopen_crm_register_people: $count[0]";

mysqli_autocommit($mysqli, FALSE);

$sql = "SELECT t1.mail, t1.language, 'active', FROM_UNIXTIME(t1.created) from users as t1";

$result = $mysqli->query($sql);

if ($result) {
	while ($row = $result->fetch_row()) {
    $resultado = buscar($mysqli, $row);
    if ($resultado != false) {
      // El registro está en la tabla, no hacemos nada
      $registrosCoincidentes++;
    } else {
      // salvamos en la tabla bbvaopen_crm_register_people
      if ($row[1] == '') {
        $row[1] = 'es';
      }
      if ($row[0] !== '') {
        $query = "INSERT into bbvaopen_crm_register_people (email,language,status,created)
                values ('".$row[0]."','".$row[1]."','".$row[2]."','".$row[3]."')";
        $mysqli->query($query);
        $registrosGuardados++;
        
      } else {
        $registrosDescartados++;
      }
    }
     
  }
  if (!mysqli_commit($mysqli)) {
    print("Falló la consignación de la transacción\n");
    exit();
  }
}

generateExportFile($mysqli, $csv_filename);

function generateExportFile($mysqli, $csv_filename) {
  $sql = "SELECT t1.sid, t1.email, t1.language, t1.status, t1.created from bbvaopen_crm_register_people as t1";
  $result = $mysqli->query($sql);
  if ($result) {
    $fp = fopen($csv_filename, 'a+');
    while ($row = $result->fetch_row()) {
      $csv_export= $row[0].','.$row[1].','.$row[2].','.$row[3].','.$row[4].PHP_EOL;
      fwrite($fp, $csv_export);
    }
    fclose($fp);
  }
}

$mysqli->close();

// Export the data and prompt a csv file for download
// header("Content-type: text/x-csv");
// header("Content-Disposition: attachment; filename=".$csv_filename."");
// echo($csv_export);

echo '<br>Registros coincidentes: '.$registrosCoincidentes;
echo '<br>Registros exportados: '.$registrosGuardados;
echo '<br>Registros descartados: '.$registrosDescartados;
echo '<br>Hay una copia del archivo en: '.getcwd().'/'.$csv_filename."\n";
$time_end = microtime(true);
$time = $time_end - $time_start;
print "<br>"."$time segundos";
print "<br>"."finalizado";


function buscar($mysqli, $row) {
  $query = "SELECT T1.email FROM bbvaopen_crm_register_people T1 WHERE T1.email = '".$row[0]."' ORDER by email";
  if ($result = $mysqli->query($query)) {
      while ($row = $result->fetch_row()) {
          return $row[0];
      }
      $result->close();
  }
  return false;
}


