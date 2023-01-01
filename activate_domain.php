#!/usr/bin/php
<?php
// Активация абонентов на ТП Недостаточно средств
// Перевод абонента на рабочий ТП

require_once 'log_lock.inc.php';
require_once 'db.inc.php';
require_once 'datafile.inc.php';
require_once 'urfa.inc.php';


$flog = open_log('activate_domain_'.date('dmY').'log');
prevent_second_run($flog);
$db = connect_mysql();
$ftext = create_arrays();

// Обрабатываем ДОМЕНЫ
$dbh->query('update temp_payments set is_locked=13 where is_locked=12');

// Выбираем абонентов на ТП Недостаточно средств
$ns_users = 'SELECT DISTINCT u.uid,
             tp.account_id, 
             atl.id as tariff_link_id, 
             th2.tariff_id  as next_tariff_id,
             a.balance + a.credit as balance,
             psd.cost cost
FROM temp_payments tp
join accounts a on a.id = tp.account_id
join account_tariff_link atl on atl.account_id=a.id
join users_accounts u on a.id=u.account_id
join tariffs_history th2 on th2.account_id = a.id 
join (select account_id, max(id) as maxid
from tariffs_history th
where th.tariff_id in (1206,188,189,190)
group by account_id) th3 on th3.maxid = th2.id
join services_data sd on sd.tariff_id=th2.tariff_id 
JOIN periodic_services_data psd ON psd.id = sd.id
WHERE  tp.is_locked=13
and atl.is_deleted=0
and u.is_deleted=0
and psd.is_deleted = 0 
AND sd.is_deleted = 0 
and atl.tariff_id = 940                            
order by tp.id';

foreach ($db->query($ns_users) as $row) {
  $user_id=$row['user_id'];
  $account_id=$row['account_id'];
  $tariff_link_id=$row['tariff_link_id'];
  $current_tariff=$row['nex_tariff_id'];
  $balance = $row['balance'];
  $cost = $row['cost'];


  $record=compact('user_id', 'account_id', 'discount_period_id', 'tariff_link_id', 'current_tariff');

  if ($balance >= $cost) {
      $db->query("update temp_payments set is_locked=14 where is_locked=13 and account_id=".$account_id);
      save_record($row);
  }
}
write_datafile('data_domain.xml');

// urfa_change_tariff('data_domain.xml');
   


# Если недостаточно денег для списания АП за домен, то удаляем запись из таблицы платежей
# Мы не списываем АП за хостинг до тех пор, пока не начислим АП за домен
$db->query("delete from temp_payments where is_locked=13");
// меняем статус поступившего платежа для проверки услуг хостинга
$db->query("update temp_payments tp
            inner join account_tariff_link atl on tp.account_id=atl.account_id
            set tp.is_locked=22
            where tp.is_locked=14
            and atl.next_tariff_id=941
            and atl.is_deleted=0;");


