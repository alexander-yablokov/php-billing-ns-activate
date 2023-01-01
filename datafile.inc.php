<?php
function create_arrays() {
    $ftext = array();
    $ftext[0][0]='<array name="user_id_array" dimension="1" comment="">';
    $ftext[1][0]='<array name="account_id_array" dimension="1" comment="">';
    $ftext[2][0]='<array name="discount_period_id_array" dimension="1" comment="">';
    $ftext[3][0]='<array name="tariff_link_id_array" dimension="1" comment="">';
    $ftext[4][0]='<array name="tariff_current_array" dimension="1" comment="">';
    return $ftext;
}


function save_record(&$ftext, $row) {
// global $ftext;
    $ftext[0][]='<dim>'.$row['user_id'].'</dim>';
    $ftext[1][]='<dim>'.$row['account_id'].'</dim>';
    $ftext[2][]='<dim>'.$row['discount_period_id'].'</dim>';
    $ftext[3][]='<dim>'.$row['tariff_link_id'].'</dim>';
    $ftext[4][]='<dim>'.$row['tariff_current'].'</dim>';

}

function write_datafile($ftext, $datafile_name) {
    $filename="/netup/utm5/scripts/ns_tariff_change/".$datafile_name;
    $fxml=fopen($filename,'w');
    $fdata='<urfa>';
    foreach ($ftext as $record) {
        foreach ($record as $param) {
         $fdata.=$param;
        }
        $fdata.='</array>';
     }
     $fdata.='</urfa>';
     //echo $fdata."\n";
     fwrite($fxml, $fdata);
     fclose($fxml);
}

