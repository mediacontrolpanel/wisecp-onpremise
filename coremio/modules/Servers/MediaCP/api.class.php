<?php
    class MediaCPAPI
    {
        private $server;
        public $error;
        public $status_code = 0;

        public $response;

        public function __construct($server = [])
        {
            $this->server = $server;
        }

        public function getHostname($add_port=true)
        {
            $url    = $this->server["secure"] ? "https://" : "http://";
            $url    .= $this->server["ip"];
            if($add_port) $url .= ":".$this->server["port"];
            return $url;
        }

        public function call($path = '', $method = NULL, $data = [], bool $catchHttpCodeErrors = true)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                "Authorization: Bearer {$this->server["access_hash"]}"
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            if ( $method ) {
                curl_setopt($ch, $method, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);


            $url = ($this->server['secure'] == 1 ? 'https' : 'http') . '://' . $this->server["ip"] .':'. $this->server['port'] . $path;

            if ( !$method ) $url .= '?'.http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);

            $responseData = curl_exec($ch);
            $this->response = $responseData;
            Modules::save_log("Servers","MediaCP",$url." - ".$path,$data,$responseData);

            if ( curl_getinfo($ch, CURLINFO_HTTP_CODE) == 401 ){
                $this->error = "Unauthorized.<br /><br />The provided API key for the server is not valid.<br /><br />Refer to <a href='https://www.mediacp.net/doc/admin-server-manual/billing/clientexec-integration-guide/'>module documentation</a> for more information.";
                Modules::save_log("Servers","MediaCP",$url." - ".$path,$data,$this->error);
                return false;
            }

            $response = json_decode($responseData);

            if ($responseData === false || (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200 && $catchHttpCodeErrors)) {
                if ( $response->errors ){
                    $this->error = "MediaCP API Error:\nCall: {$path}\n";
                    foreach($response->errors as $message)
                        $this->error .= "{$message}\n";
                }else{
                    $this->error = "MediaCP API Request / cURL Error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ' - '.curl_error($ch) . '<br /><br />' . print_r($response ?: $data, true) . "<br /><br />Refer to <a href='https://www.mediacp.net/doc/admin-server-manual/billing/wisecp-integration-guide/'>module documentation</a> for more information.";
                }

                Modules::save_log("Servers","MediaCP",$url." - ".$path,$data,$this->error);
                return false;
            }
            curl_close($ch);

            return $response;
        }


    }