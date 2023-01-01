#!/usr/bin/php
<?php
// Активация абонентов на ТП Недостаточно средств
// Перевод абонента на рабочий ТП

require_once 'log_lock.inc.php';
require_once 'db.inc.php';
require_once 'datafile.inc.php';
require_once 'urfa.inc.php';


$flog = open_log('activate_hosting_'.date('dmY').'log');
prevent_second_run($flog);
$db = connect_mysql();
$ftext = create_arrays();

# Обрабатываем ХОСТИНГ
$db->query("update temp_payments set is_locked=23 where is_locked=22");


// Выбираем абонентов на ТП Недостаточно средств
/*
188	Регистрация домена в зоне .tj
189	Регистрация домена в зонах .com, .net, .org (для юрлиц) [1]
190	Регистрация домена в зонах .biz, .info
629	Хостинг. Недостаточно средств
735	Недостаточно средств за домен в зоне tj
736	Недостаточно средств за домен в зонах .com, .net, .org, .biz, .info
940	Недостаточно средств за Домен [0][1]
941	Недостаточно средств за Хостинг [0][1]
1206	NEW Регистрация домена в зоне .tj [0]
*/

$ns_users = 'SELECT DISTINCT u.uid,
                             tp.account_id, 
                             atl.id as tariff_link_id, 
                             th2.tariff_id  as next_tariff_id,
                             a.balance + a.credit as balance,
                             psd.cost cost
             from temp_payments tp
             join accounts a on a.id = tp.account_id
             join account_tariff_link atl on atl.account_id=a.id
             join users_accounts u on a.id=u.account_id
             join tariffs_history th2 on th2.account_id = a.id 
             join (select account_id, max(id) as maxid
                   from tariffs_history th
                   where th.tariff_id not in (1206,188,189,190,940,941,629,735,736)
                   group by account_id) th3 on th3.maxid = th2.id
             join services_data sd on sd.tariff_id=th2.tariff_id 
             join periodic_services_data psd ON psd.id = sd.id
             where tp.is_locked=23
             and atl.is_deleted=0
             and u.is_deleted=0
             and psd.is_deleted = 0 
             and sd.is_deleted = 0 
             and atl.tariff_id = 941                            
             order by tp.id';

$count = 0;
foreach ($db->query($ns_users) as $row) {
    $user_id=$row['user_id'];
    $account_id=$row['account_id'];
    $tariff_link_id=$row['tariff_link_id'];
    $current_tariff=$row['nex_tariff_id'];
    $balance = $row['balance'];
    $cost = $row['cost'];


    $record=compact('user_id', 'account_id', 'discount_period_id', 'tariff_link_id', 'current_tariff');

    if ($balance >= $cost) {
        save_record($row);
        write_log("За хостинг.\tЛС=$account_id\tТП=$last_next_t_id\tбаланс+кредит=$balance\tАП=$cost");
    }
}
write_datafile('data_hosting.xml');

// urfa_change_tariff('data_hosting.xml');

$db->query("delete from temp_payments where is_locked=23");






  
