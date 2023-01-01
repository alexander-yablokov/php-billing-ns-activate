#!/usr/bin/php
<?php
// Активация абонентов на ТП Недостаточно средств
// Перевод абонента на рабочий ТП

require_once 'log_lock.inc.php';
require_once 'db.inc.php';
require_once 'datafile.inc.php';
require_once 'urfa_new.inc.php';


$flog = open_log('activate_'.date('dmy').'.log');
prevent_second_run($flog);
$db = connect_mysql();
$ftext = create_arrays();
$ftext_flexible = create_arrays();

 // ВСЕ ПЕРЕРАСЧЕТЫ ДОЛЖНЫ БЫТЬ ПОДТВЕРЖДЕНЫ ЛИСТОМ СОГЛАСОВАНИЯ

// ДОМЕНЫ И ХОСТИНГ
// Сначала начислим АП за домен, потом за хостинг

// Помечаем платежи за домены 
// tp.is_locked=12 
$db->query("update temp_payments tp
            inner join account_tariff_link atl on tp.account_id=atl.account_id
            set tp.is_locked=12
            where tp.is_locked=0
            and atl.next_tariff_id=940
            and atl.is_deleted=0;");
// Помечаем платежи за хостинг 
// tp.is_locked=22
// если у абонента нет неоплаченного домена: tp.is_locked остался 0
$db->query("update temp_payments tp
            inner join account_tariff_link atl on tp.account_id=atl.account_id
            set tp.is_locked=22
            where tp.is_locked=0 
            and atl.next_tariff_id=941
            and atl.is_deleted=0;");


// ТАРИФЫ ИНТЕРНЕТ
$db->query("update temp_payments 
            set is_locked=3 
            where is_locked=0");

// Выбираем абонентов на ТП Недостаточно средств
$ns_users = 'SELECT DISTINCT u.uid as user_id,
                            tp.account_id, 
                            atl.id as tariff_link_id, 
                            atl.next_tariff_id,
                            a.balance + a.credit as balance,
                            ifnull(osd.cost*(1+a.vat_rate)*(1+a.sale_tax_rate), psd.cost*(1+a.vat_rate)*(1+a.sale_tax_rate)) cost,
                            ifnull(osd.id, 0) as once_service_id,
                            ifnull(tf.tariff_id, 0) flexible_tariff,
                            if (dp.end_date > unix_timestamp()  + 432000, 1, 0) has_enough_time
            FROM temp_payments tp
            join accounts a on a.id = tp.account_id
            join account_tariff_link atl on atl.account_id=a.id
            join discount_periods dp on atl.discount_period_id = dp.id
            join users_accounts u on a.id=u.account_id
            join services_data sd on sd.tariff_id=atl.next_tariff_id 
            JOIN periodic_services_data psd ON psd.id = sd.id
            left join wholesale_tariffs wt on wt.tariff_id=atl.next_tariff_id
            left join once_service_data osd on osd.id=wt.once_service_id 
            left join tariffs_flexible tf on tf.tariff_id=atl.next_tariff_id
            WHERE  tp.is_locked=3
            and atl.is_deleted=0
            and u.is_deleted=0
            and psd.is_deleted = 0 
            AND sd.is_deleted = 0 
            AND sd.service_type = 3
            and atl.tariff_id IN (497,498,1171)
            and atl.next_tariff_id not in
            (497,498,1171,
            1161,1165,1170,
            1167,1168,1169)
            ORDER BY tp.id';



$flexible_dp = get_flexible_dp($db);
foreach ($db->query($ns_users) as $row) {
  $user_id=$row['user_id'];
  $account_id=$row['account_id'];
  $tariff_link_id=$row['tariff_link_id'];
  $tariff_current=$row['next_tariff_id'];
  $balance = $row['balance'];
  $cost = $row['cost'];
  $flexible_tariff = $row['flexible_tariff'];
  $has_enough_time = $row['has_enough_time'];

  $record=compact('user_id', 'account_id', 'discount_period_id', 'tariff_link_id', 'tariff_current');

  if ($balance >= $cost) {
    if ($flexible_tariff > 0) {
      $record['discount_period_id']=$flexible_dp;
      save_record($ftext_flexible, $record);
      write_log($flog, "ЛС-flexible $account_id\tбаланс ".round($balance,2)."\tцена ".round($cost,2)."\tid тарифа $tariff_current\tрасчетный период ".$record['discount_period_id']);
    } else if ($has_enough_time > 0) {
      $record['discount_period_id']=get_account_dp($db, $account_id);
      save_record($ftext, $record);
      write_log($flog, "ЛС-fixed $account_id\tбаланс ".round($balance,2)."\tцена ".round($cost,2)."\tid тарифа $tariff_current\tрасчетный период ".$record['discount_period_id']);
    } 
  }
}
$datafile_name='activate.dat';
$datafile_flexible_name='activate_flexible.dat';

write_datafile($ftext, $datafile_name);
write_datafile($ftext_flexible, $datafile_flexible_name);

urfa_change_flexible_tariff($datafile_flexible_name);
urfa_change_tariff($datafile_name);

$db->query("delete from temp_payments where is_locked=3");
