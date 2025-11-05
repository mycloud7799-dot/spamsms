<?php
header('Content-Type: application/json; charset=utf-8');

class VIETTELPAY
{
    private $imei = 'VTP_6CE52C1296D839D385E51CD63B9B85EC';

    private function getHeaders($accessToken = null)
    {
        $headers = [
            'Host: api8.viettelpay.vn',
            'product: VIETTELPAY',
            'accept-language: vi',
            'authority-party: APP',
            'channel: APP',
            'type-os: android',
            'app-version: 5.1.1',
            'os-version: 11',
            'imei: ' . $this->imei,
            'x-request-id: ' . $this->getRequestId(),
            'user-agent: okhttp/4.2.2',
            'Content-Type: application/json; charset=UTF-8',
            'Accept-Encoding: gzip',
        ];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }
        return $headers;
    }

    private function getRequestId()
    {
        return date('YmdHis');
    }

    private function CURL($url, $headers, $data = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => !empty($data),
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => "",
            CURLOPT_HEADER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            return ['error' => curl_error($curl)];
        }
        curl_close($curl);
        $json = json_decode($response, true);
        return json_last_error() === JSON_ERROR_NONE ? $json : ['raw_response' => $response];
    }

    public function process($phone)
    {
        $headers = $this->getHeaders();

        // Bước 1: Login
        $data = json_encode([
            "username" => $phone,
            "type" => "msisdn"
        ]);
        $login = $this->CURL('https://api8.viettelpay.vn/customer/v1/validate/account', $headers, $data);

        $info = null;
        $rank = null;

        // Bước 2 & 3 nếu có accessToken
        if (isset($login['data']['accessToken'])) {
            $accessToken = $login['data']['accessToken'];
            $headersWithToken = $this->getHeaders($accessToken);

            // Bước 2: Thông tin cá nhân
            $info = $this->CURL(
                'https://api8.viettelpay.vn/customer-ekyc/v1/standardized-profile/get-personal-information',
                $headersWithToken
            );

            // Bước 3: Lấy hạng tài khoản Viettel++
            $rank = $this->CURL(
                "https://api8.viettelpay.vn/loyalty/mobile/v2/accounts/get-account-rank-vtt?msisdn=$phone",
                $headersWithToken
            );
        }

        return [
            'login' => $login,
            'personal_info' => $info,
            'viettel_plus_rank' => $rank
        ];
    }
}

// Nhận số điện thoại
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
if (empty($phone)) {
    echo json_encode(['error' => 'Thiếu tham số phone']);
    exit;
}

$vtp = new VIETTELPAY();
echo json_encode($vtp->process($phone), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
