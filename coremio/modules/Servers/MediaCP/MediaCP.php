<?php
    class MediaCP_Module extends ServerModule
    {
        private $api;
        function __construct($server,$options=[])
        {
            $this->_name = __CLASS__;

            // If you are developing, set the $this->force_setup value to be false.
            $this->force_setup  = false; // If "true", even if the module gets an error message, it will be ignored and the installation is complete.
            parent::__construct($server,$options);
        }

        protected function define_server_info($server=[])
        {
            if(!class_exists("MediaCPAPI")) include __DIR__.DS."api.class.php";
            $this->api = new MediaCPAPI($server);
            $this->server = $server;
        }

        public function testConnect(){

            if (!$this->api->call("/api/0/media-service/list", null, [
                'user_id' => 0
            ])) {
                $this->error = $this->api->error;
                return false;
            }

            return true;
        }

        public function config_options($data=[])
        {
            return [
                'plugin'          => [
                    'wrap_width'        => 100,
                    'name'              => "Media Service",
                    'description'       => "",
                    'type'              => "dropdown",
                    'options'           => [
                        'shoutcast198' => 'Audio - Shoutcast 198',
                        'shoutcast2' => 'Audio - Shoutcast 2',
                        'icecast' => 'Audio - Icecast 2',
                        'icecast_kh' => 'Audio - Icecast 2 KH',
                        'AudioTranscoder' => 'Audio - Transcoder',
                        'WowzaMedia' => 'Video - Wowza Streaming Engine',
                        'Flussonic' => 'Video - Flussonic',
                        'NginxRtmp' => 'Video - Nginx-Rtmp',
                    ],
                    'value'             => isset($data["plugin"]) ? $data["plugin"] : "shoutcast2",
                ],

                'sourceplugin'          => [
                    'wrap_width'        => 100,
                    'name'              => "AutoDJ Type",
                    'description'       => "",
                    'type'              => "dropdown",
                    'value'             => isset($data["sourceplugin"]) ? $data["sourceplugin"] : "-",
                    'options'           => [
                        '-' => 'No AutoDJ Service',
                        'liquidsoap' => 'Liquidsoap',
                        'ices04' => 'Ices 0.4',
                        'ices20' => 'Ices 2.0',
                        'sctransv1' => 'Shoutcast Transcoder V1',
                        'sctransv2' => 'Shoutcast Transcoder V2',
                    ]
                ],
                'servicetype'          => [
                    'wrap_width'        => 100,
                    'name'              => "Video Service Type",
                    'description'       => "",
                    'type'              => "dropdown",
                    'value'             => isset($data["servicetype"]) ? $data["servicetype"] : "-",
                    'options'           => [
                        'Live Streaming',
                        'TV Station',
                        'Ondemand Streaming',
                        'Stream Relay',
                    ]
                ],

                'maxuser'          => [
                    'wrap_width'        => 100,
                    'name'              => "Listeners / Viewers",
                    'description'       => "",
                    'type'              => "text",
                    'value'             => isset($data["maxuser"]) ? $data["maxuser"] : "100",
                    'placeholder'       => "e.g., 100",
                ],
                'bitrate'          => [
                    'wrap_width'        => 100,
                    'name'              => "Bitrate (Kbps)",
                    'description'       => "",
                    'type'              => "dropdown",
                    'value'             => isset($data['bitrate']) ? $data['bitrate'] : '128',
                    'options'           => explode(",", "24,32,40,48,56,64,80,96,112,128,160,192,224,256,320,400,480,560,640,720,800,920,1024,1280,1536,1792,2048,2560,3072,3584,4096,4068,5120,5632,6144,6656,7168,7680,8192,9216,10240,11264,12228,13312,14336,99999")
                ],

                'bandwidth'          => [
                    'wrap_width'        => 100,
                    'name'              => "Bandwidth (MB)",
                    'description'       => "",
                    'type'              => "text",
                    'value'             => isset($data["bandwidth"]) ? $data["bandwidth"] : "Unlimited",
                    'placeholder'       => "e.g., 100",
                ],
                'quota'          => [
                    'wrap_width'        => 100,
                    'name'              => "Disk Space (MB)",
                    'description'       => "",
                    'type'              => "text",
                    'value'             => isset($data["quota"]) ? $data["quota"] : "1024",
                    'placeholder'       => "e.g., 100",
                ],
                'streamtargets'          => [
                    'wrap_width'        => 100,
                    'name'              => "Stream Targets",
                    'description'       => "",
                    'type'              => "text",
                    'value'             => isset($data["streamtargets"]) ? $data["streamtargets"] : "streamtargets",
                    'placeholder'       => "e.g., 100",
                ],
            ];
        }
        
        public function generate_username($domain='',$half_mixed=false){
            $exp            = explode(".",$domain);
            $domain         = Filter::transliterate($exp[0]);
            $username       = $domain;
            $fchar          = substr($username,0,1);
            $size           = strlen($username);
            if($fchar == "0" || (int)$fchar)
                $username   = Utility::generate_hash(1,false,"l").substr($username,1,$size-1);

            if($size>=8){
                $username   = substr($username,0,5);
                $username .= Utility::generate_hash(3,false,"l");
            }elseif($size>4 && $size<9){
                $username   = substr($username,0,5);
                $username .= Utility::generate_hash(3,false,"l");
            }elseif($size>=1 && $size<5){
                $how        = (8 - $size);
                $username   = substr($username,0,$size);
                $username .= Utility::generate_hash($how,false,"l");
            }

            return $username;
        }

        public function buildServiceParams($userId, $password, $order_options)
        {

            # Create Service
            $params = [
                'userid' => $userId,
                'plugin' => $order_options["creation_info"]['plugin'],
                'servicetype' => $order_options["creation_info"]['servicetype'] == '-' ? '' : $order_options["creation_info"]['servicetype'],
                'maxuser' => $order_options["creation_info"]['maxuser'],
                'bitrate' => $order_options["creation_info"]['bitrate'],
                'bandwidth' => $order_options["creation_info"]['bandwidth'],
                'quota' => $order_options["creation_info"]['quota'],
                'stream_targets_limit' => strtolower($order_options["creation_info"]['streamtargets'])=='unlimited' ? -1 : $order_options["creation_info"]['streamtargets'],
            ];

            switch($params['plugin']){
                case 'shoutcast198':
                case 'shoutcast2':
                    $params['password'] = $password;
                    $params['adminpassword'] = Utility::generate_hash(12);
                    break;

                case 'icecast':
                case 'icecast_kh':
                    $params['source_password'] = $password;
                    $params['password'] = Utility::generate_hash(12);
                    break;
            }

            # Source Plugin
            if (in_array($params['plugin'], ['shoutcast198','shoutcast2','icecast','icecast_kh']) && !empty($order_options["creation_info"]['sourceplugin']) && $order_options["creation_info"]['sourceplugin'] != '-') {
                $params['sourceplugin'] = $order_options["creation_info"]['sourceplugin'];
            }

            return $params;
        }

        public function create($domain = '',array $order_options=[])
        {
            $username       = $this->user['email'];
            $password       = Utility::generate_hash(12);

            if(isset($order_options["username"]) && $order_options["username"]) $username = $order_options["username"];
            if(isset($order_options["password"]) && $order_options["password"]) $password = $order_options["password"];

            try
            {
                # Create Customer Account or catch already exists gracefully
                $userPassword = $password;
                $response = $this->api->call("/api/0/user/store", CURLOPT_POST, [
                    'name' => $this->user['full_name'],
                    'username' => $username,
                    'user_email' => $this->user['email'],
                    'password' => $userPassword,
                ]);
                if ( isset($response->errors) && (isset($response->errors->username) || $response->errors->username == 'Username must be unique') ){
                    $user = $this->api->call("/api/0/user/show", NULL, ['username'=>$username]);
                    $userPassword = '[EXISTING PASSWORD]';
                }else{
                    $user = $response->user;
                }

                if ( !$user || empty($user->id) ) throw new Exception("User was not created successfully.");


                $response = $this->api->call("/api/0/media-service/store", CURLOPT_POST, $this->buildServiceParams($user->id, $password, $order_options));
                if ( $response->status != 1 ) throw new Exception("Unable to create service.\n\n{$response->error}\n\nDebugging: " . print_r($response,true));

                #$this->userPackage->setCustomField("User Name", $args['customer']['email']);
                #$this->setCustomProperty( 'ServiceID', $response->service_id);
                #$this->setCustomProperty( 'PortBase', $response->return->portbase);

                $response = $this->api->call("/api/{$response->service_id}/media-service/show");
                #$this->userPackage->setCustomField($args['server']['variables']['plugin_mediacp_Service_Name_Custom_Field'], $response->unique_id, CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates
                #$this->userPackage->setCustomField($args['server']['variables']['plugin_mediacp_Service_Portbase_Custom_Field'], $response->portbase, CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates
                #$this->userPackage->setCustomField($args['server']['variables']['plugin_mediacp_Service_Password_Custom_Field'], $response->password, CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates


                /*
                 * $order_options or $this->order["options"]
                * for parameters: https://docs.wisecp.com/en/kb/hosting-panel-module-development-parameters
                * Here are the codes to be sent to the API...
                */
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            return [
                'username' => "$response->portbase|$user->id|$response->id",
                'password' => $response->password,
            ];
        }

        public function suspend()
        {
            try
            {
                list($portbase, $customerId, $serviceId) = explode("|",$this->config['user']);
                $response = $this->api->call("/api/{$serviceId}/media-service/suspend", CURLOPT_POST, [
                    'reason' => 'Suspended by billing system',
                    'days' => 0
                ]);
                $result             = "OK"; #$this->api->suspend();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            return true;
        }

        public function unsuspend()
        {
            try
            {
                list($portbase, $customerId, $serviceId) = explode("|",$this->config['user']);
                $response = $this->api->call("/api/{$serviceId}/media-service/unsuspend", CURLOPT_POST, []);
                $result = "OK"; #$this->api->unsuspend();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
                return true;
            else
            {
                $this->error = $result;
                return false;
            }
        }

        public function terminate()
        {
            try
            {
                list($portbase, $customerId, $serviceId) = explode("|",$this->config['user']);

                $response = $this->api->call("/api/{$serviceId}/media-service/delete", CURLOPT_POST, []);


                # Delete user account if they have no other services
                $servers = $this->api->call("/api/0/media-service/list", NULL, ['user_id'=>$customerId]);
                if ( !isset($servers->message) && count($servers) === 0 ){
                    $this->api->call("/api/0/user/delete/{$customerId}");
                }

                $result = "OK"; # $this->api->terminate();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Error Message";
            */

            return true;
        }

        public function change_password($password=''){
            try
            {
                list($portbase, $customerId, $serviceId) = explode("|", $this->config['user']);
                $this->api->call("/api/{$customerId}/user/update", CURLOPT_POST, ['password'=>$password]);
                $this->api->call("/api/{$serviceId}/media-service/update", CURLOPT_POST, $this->buildServiceParams($customerId,$password,$this->options));
                $result = "OK"; # $this->api->change_password($this->>config["user"],$password);
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
                return true;
            else
            {
                $this->error = $result;
                return false;
            }
        }

        public function apply_updowngrade($orderopt=[],$product=[]){
            $o_creation_info        = $orderopt["creation_info"];
            $p_creation_info        = $product["module_data"];
            try
            {

                list($portbase, $customerId, $serviceId) = explode("|", $this->config['user']);

                $response = $this->api->call("/api/{$serviceId}/media-service/update", CURLOPT_POST, $this->buildServiceParams($customerId, $this->config['password'],$orderopt));
                if ( !$response || $response->status !== true){
                    $this->error = "Unable to update service:<br />" . $this->api->response;
                    return false;
                }

                $result = "OK"; # $this->api->modify_account($this->config["user"],$params);
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
                return true;
            else
            {
                $this->error = $result;
                return false;
            }
        }


        public function listAccounts(){

            $accounts = [];
            try
            {
                $response = $this->api->call("/api/0/media-service/list");
                foreach ($response as $service){
                    $accounts[] = [
                        'domain' => $this->server['name'],
                        'username' => "{$service->portbase}|{$service->user_id}|{$service->id}",
                    ];
                }
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Error Message";
            */

            return $accounts;

        }

        public function clientArea()
        {
            $content    = $this->clientArea_buttons_output();
            $_page      = $this->page;

            if(!$_page) $_page = 'home';

            list($portbase, $customerId, $serviceId) = explode("|", $this->config['user']);
            $content .= $this->get_page('clientArea-'.$_page,
                [
                    'server' => $this->server,
                    'user' => $this->user,
                    'config' => $this->config,
                    'data' => $this->options["creation_info"],
                    'portbase' => $portbase,
                ]
            );
            return  $content;
        }

        public function clientArea_buttons()
        {
            $buttons    = [];

            return $buttons;
        }


        public function adminArea_buttons()
        {
            $buttons = [];

            return $buttons;
        }

        public function use_adminArea_custom_transaction()
        {
            echo  Utility::jencode([
                'status' => "successful",
                'message' => 'Successful Transaction',
            ]);

            return true;
        }

        public function use_adminArea_custom_function()
        {
            if(Filter::POST("var2"))
            {
                echo  Utility::jencode([
                    'status' => "successful",
                    'message' => 'Successful message',
                ]);
            }
            else
            {
                echo "Content Here...";
            }

            return true;
        }

        public function use_adminArea_SingleSignOn()
        {
            $api_result     = 'OK|bmd5d0p384ax7t26zr9wlwo4f62cf8g6z0ld';

            if(substr($api_result,0,2) != 'OK'){
                echo "An error has occurred, unable to access.";
                return false;
            }

            $token          = substr($api_result,2);
            $url            = "https://{$this->server['name']}:{$this->server['port']}";

            Utility::redirect($url);

            echo "Redirecting...";
        }


        public function save_adminArea_service_fields($data=[])
        {

            /* OLD DATA */
            $o_c_info = $data['old']['creation_info'];
            $o_config = $data['old']['config'];
            $o_ftp_info = $data['old']['ftp_info'];
            $o_options = $data['old']['options'];

            /* NEW DATA */

            $n_c_info = $data['new']['creation_info'];
            $n_config = $data['new']['config'];
            $n_ftp_info = $data['new']['ftp_info'];
            $n_options = $data['new']['options'];

            list($portbase, $customerId, $serviceId) = explode("|", $this->config['user']);

            if (!empty($customerId) && !empty($serviceId)) {
                if ($o_c_info !== $n_c_info || $o_config !== $n_config) {
                    $response = $this->api->call("/api/{$serviceId}/media-service/update", CURLOPT_POST, $this->buildServiceParams($customerId, $data['new']['config']['password'], $data['new']));
                    if ( !$response || $response->status !== true){
                        $this->error = "Unable to update service:<br />" . $this->api->response;
                        return false;
                    }
                }

            }

            if($n_c_info['field1'] == '')
            {
             #   $this->error = 'Do not leave Field 1 empty.';
             #   return false;
            }

            if($o_options['disk_limit'] != $n_options['disk_limit'])
            {
                /* Example: Change Disk Limit
                if(!$this->change_disk_quota($n_options["disk_limit"])) return false;
                */
            }

            return [
                'creation_info'     => $n_c_info,
                'config'            => $n_config,
                'ftp_info'          => $n_ftp_info,
                'options'           => $n_options,
            ];
        }



    }


   // Hook Usage Sample
/*
    Hook::add("changePropertyToAccountOrderDetails",1,function($params = [])
    {
        if($params["module"] == "SampleHostingCP" && !Filter::isPOST())
        {
            $options        = $params["options"];
            Helper::Load("Products");
            $server         = Products::get_server($options["server_id"]);
            if($server) $options["ip"] = $server["ip"];
            $params["options"] = $options;
            return $params;
        }
    });
*/


Hook::add("changePropertyToAccountOrderDetails",1,function($params = [])
{
    if($params["module"] == "MediaCP" && !Filter::isPOST())
    {
        $options        = $params["options"];

        Helper::Load("Products");
        $server         = Products::get_server($options["server_id"]);
        if($server) $options["domain"] = $server["name"];

        if(isset($options["ftp_info"]) && $options["ftp_info"]) unset($options["ftp_info"]);
        $options["disable_showing_resource_limits"] = true;
        $params["options"] = $options;
        return $params;
    }
});

Hook::add("AdminAreaEndBody",1,function(){
    if(in_array(View::$init->template_file,['add-hosting.php','edit-hosting.php','hosting-order-detail.php']))
        return '
<script type="text/javascript">
$(document).ready(function(){
    if(document.getElementById("select-shared-server") && $("#select-shared-server option:selected").html().indexOf("MediaCP") !== -1) toggle_features(0);
    if(document.getElementById("server_info") && $("#server_info").html().indexOf("MediaCP") !== -1) toggle_features(0);
    $("#select-shared-server").change(function(){
        if($("#select-shared-server option:selected").html().indexOf("MediaCP") === -1) toggle_features(1);
        else toggle_features(0);
    });
});

function toggle_features(a = 0)
{
    if(a === 1)
    {
        $("#MediaCP_packages").remove();
        $("input[name=domain]").parent().parent().css("display","block");
    }
    else
    {
        $("input[name=domain]").parent().parent().css("display","none");
    }
    
    $("#disk_limit_container").parent().css("display",a === 1 ? "block" : \'none\');
    $("#bandwidth_limit_container").parent().css("display",a === 1 ? "block" : "none");
    $("#email_limit_container").parent().css("display",a === 1 ? "block" : "none");
    $("#database_limit_container").parent().css("display",a === 1 ? "block" : "none");
    $("#addons_limit_container").parent().css("display",a === 1 ? "block" : "none");
    $("#subdomain_limit_container").parent().css("display",a === 1 ? "block" : "none");
    $("#ftp_limit_container").parent().css("display",a === 1 ? "block" : "none");
    $("#park_limit_container").parent().css("display",a === 1 ? "block" : "none");
    $("#max_email_per_hour_container").parent().css("display",a === 1 ? "block" : "none");
    $("input[name=cpu_limit]").parent().parent().css("display",a === 1 ? "block" : "none");
    $("#feature-CloudLinux").parent().parent().css("display",a === 1 ? "block" : "none");
    $("#dns_content").css("display","none");
}
</script>
        ';
});