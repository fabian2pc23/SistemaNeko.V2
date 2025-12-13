<?php
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ1c2VybmFtZSI6IlNlcnlvUHJvZ3JhbSIsImlhdCI6MTc2NTM0NTcxMywiZXhwIjoxNzY1NDMyMTEzfQ.KAnD0O-yrO5DFm6QI851708CloBTTdLmWWm4LSCIsWIeeXjfkR1v5r0idlWfC6--KGkSREPf3iIhwPxBCDHwQ69RCBSOv1Mc_yGMi0lhnqDR2Ty0vLtskgkWij-XnAgCCNju8W33rP6h8TbGLT_yCz3hJxCw32IeLWmPd7a29RXhzi_Z-w7aK6v21CLoU1Lmw6VKjazBpOMho3qjpojzddbgZpcFDt8CIvfkMd2bba8SSFXF_h5OWFzyz9Ce8dw0D_4cQSKkexFGoRYzGk4pndJ4PR48zBTX8xx3G09IkW-h_JXlrXxrW27OWCMUL4ylFfnQcD9bLD8zDe_SYsT4Jh_jlILhLFCU-NnUIV2c1Szgw5OScqRAkEKRvUiB-JnuCmJjsUBSH1TbaiYNgVC5Zu62kb7khR1W_cyhvscUJGhjRMbeM1Tv5-_J-hE-0D3C0NfTpU6q5_lHGk0KjNSAvglMZGVb40wvBph8ZjkIRbBbZGS-9phZ1xSGPvKD8VrH7W7pKll9URbhdka2enFtwFsl6hguzxd-Gv4dd1W_J2F7vbq34AcfO5Nl-5X8_aZD4tWC64QEniUWno-LuR6-_U9FfrHkFXLX_qEL4wANaDbz4kB69wjew9ZNHrK7evBD5GPHIpB1kCsOPBm-ikIA3t3qYp8l3Tde2Tn86hFxiMU';

$data = [
    "ruc" => "10406980788",
    "razonSocial" => "CORTEZ FLORES ANDREA DEL CARMEN",
    "direccion" => "CAR. LAMBAYEQUE CARRETERA LAMBAYEQUE (FRENTE AL GRIFO PRIMAX)",
    "solUser" => "MODODEMO", // Intento genérico o vacío
    "solPass" => "MODODEMO",
    "certificado" => "", // Opcional?
];

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://facturacion.apisperu.com/api/v1/companies",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . trim($token)
    ),
    CURLOPT_SSL_VERIFYPEER => false
));

$response = curl_exec($curl);
echo $response;
