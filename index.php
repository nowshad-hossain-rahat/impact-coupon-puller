<?php

$settings = [
    'last_pushed_at' => '2022-04-09 12:05:00',
    'schedule_posting' => 'hourly'
];

$last_post_at = strtotime( $settings['last_pushed_at'] );
$current_time = strtotime( date('Y-m-d H:i:s') );
$diff = ( $current_time - $last_post_at ) / 60 / 60;
$schedule = $settings['schedule_posting'];

echo date('Y-m-d H:i:s') , "\n";
echo $diff , "\n";

if( 
    ( $schedule == 'hourly' && $diff >= 1 ) || 
    ( $schedule == 'every_two_hours' && $diff >= 2 ) || 
    ( $schedule == 'twice_a_day' && $diff >= 12 ) || 
    ( $schedule == 'daily' && $diff >= 24 )
){
    echo 'It\'s time';
}

?>