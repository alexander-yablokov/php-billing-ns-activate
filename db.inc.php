<?php


function connect_mysql()
{
  global $flog;

  $host_work = '';
  $db_work = '';
  $user_work = '';
  $pass_work = '';
  $port_work = "3306";
  $charset_work = 'utf8mb4';

  $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
  ];
  $dsn_work = "mysql:host=$host_work;dbname=$db_work;charset=$charset_work;port=$port_work";
  try {
    return new PDO($dsn_work, $user_work, $pass_work, $options);
  } catch (PDOException $e) {
    write_log($flog, "Ошибка: Не удалось установить соединение с базой данных: " .
      $e->getMessage());
    exit;
  }
}


function run_sql($db, $sql, &$stmt, $params = [])
{
  if (!$stmt) {
    $stmt = $db->prepare($sql);
    if (!$stmt) {
      throw new PDOException("$sql could not be prepared");
    }
  }
  if (empty($params)) {
    $result = $stmt->execute();
  } else {
    $result = $stmt->execute($params);
  }
  if (!$result) {
    throw new PDOException("$sql with params " . implode(",", $params) . " execute result is false");
  }
  return $stmt;
}

function get_flexible_dp($db)
{
  static $stmt_flexible_dp = null;
  $sql_flexible_dp = "SELECT id FROM discount_periods 
                      WHERE static_id = ? AND is_expired = 0 
                      and next_discount_period_id = 0";
  $today = date('d');
  $row = run_sql($db, $sql_flexible_dp, $stmt_flexible_dp, [$today])->fetch();
  return $row['id'];
}


function get_account_dp($db, $account_id)
{
  static $stmt_account_dp = null;
  $sql_account_dp = "SELECT discount_period_id as id
  FROM account_tariff_link 
  WHERE account_id = ? AND is_deleted = 0";
  $row = run_sql($db, $sql_account_dp, $stmt_account_dp, [$account_id])->fetch();
  return $row['id'];
}

function get_last_tp($db, $account_id)
{
  static $stmt_last_tp;
  $sql_last_tp = "select tariff_id as id,
  from tariffs_history th
  where account_id= ?
  and th.tariff_id in (1206,188,189,190)
  order by link_date desc
  limit 1";
  $row = run_sql($db, $sql_last_tp, $stmt_last_tp, [$account_id])->fetch();
  return $row['id'];
}