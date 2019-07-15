<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class BulkNotification_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    public function multiple_push($groupid, $multiple, $msg_id, $message) {
        $qry_grp = $this->db->select("group_name")
                ->where("id", $groupid)
                ->get("business_group");
        $grp_data = $qry_grp->row();
        $grp_name = $grp_data->group_name;

        if (!empty($multiple)) {
            $message_notify = array(
                "type" => "bgrp",
                "msg" => $message,
                "title" => "JOS",
                "group_name" => $grp_name,
            );
            // For Notification @START// 
            $id = array_column($multiple, "user_id");
            $implode = implode(",", $id);
            $notify_data = array(
                "notification_type" => "bgrp", // business group msg
                "group_id" => $groupid,
                "msg_id" => $msg_id,
                "user_id" => $implode,                
            );
            $this->db->insert('notification', $notify_data);
            $insert_id = $this->db->insert_id();
            // For Notification @END// 
            $tokens_android = array();
            $tokens_ios = array();
            foreach ($multiple as $row) {
                if ($row['device_type'] == 'android' && $row['device_token2'] != '' && $row['device_token2'] != '0') {
                    $tokens_android[] = $row['device_token2'];
                } elseif ($row['device_type'] == 'iOS' && $row['device_token2'] != '' && $row['device_token2'] != '0') {
                    $tokens_ios[] = $row['device_token2'];
                }
            }
            $limit = 1000;

            //for android #START
            if (!empty($tokens_android)) {
                $for_android = array_chunk($tokens_android, $limit);
                foreach ($for_android as $registatoin_ids) {
                    $this->send_notification_to_android($registatoin_ids, $message_notify);
                }
            }
            //for android #END
            //for ios #START
            if (!empty($tokens_ios)) {
                $for_ios = array_chunk($tokens_ios, $limit);
                foreach ($for_ios as $registatoin_ids) {
                    $this->send_notification_to_ios($registatoin_ids, $message_notify);
                }
            }
            //for ios #START
        }
    }

    public function send_notification_to_android($registatoin_ids, $message_notify) {
        $registrationIds = $registatoin_ids;
        $response['notification'] = $message_notify;
        $result = json_encode($response);
        $msg = array
            (
            "body" => $message_notify["msg"],
            "title" => "JOS",
            "vibrate" => 1,
            "sound" => "/res/raw/beep.wav",
            "click_action" => "OPEN_ACTIVITY_1",
        );

        $data = array
            (
            'body' => $result,
            'title' => 'JOS',
            'vibrate' => 1,
            'sound' => 1,
            "click_action" => "OPEN_ACTIVITY_1",
        );

        $fields = array
            (
            'registration_ids' => $registrationIds,
            'notification' => $msg,
            'data' => $data
        );


        $headers = array
            (
            // 'Authorization: key=' . '--key--', // for staging server.   
            'Authorization: key=' . '--key--', // for live server.
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function send_notification_to_ios($registatoin_ids, $message_notify) {

        $gateway_url = 'ssl://gateway.sandbox.push.apple.com:2195'; // for staging
        //$gateway_url = 'ssl://gateway.push.apple.com:2195';       // for live    
        $passphrase = 'Mobiloitte1';
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', '--pem path--');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        $fp = stream_socket_client($gateway_url, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        if ($err) {
            return false;
        }
        foreach ($registatoin_ids as $device_token) {
            $body['aps'] = array(
                'alert' => $message_notify['msg'],
                'info' => $message_notify,
                'sound' => 'default',
                'badge' => 0,);
            $payload = json_encode($body);
            $msg = chr(0) . pack('n', 32) . pack('H*', $device_token) . pack('n', strlen($payload)) . $payload;
            $result = fwrite($fp, $msg, strlen($msg));
        }
        fclose($fp);
    }

}
