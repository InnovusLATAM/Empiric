<?php
chdir(__DIR__);
include_once '../EndPoint.php';
chdir(__DIR__);
include_once '../../api/DataTypes.php';
chdir(__DIR__);
include_once '../../api/Request.php';
chdir(__DIR__);
include_once '../../api/Response.php';
chdir(__DIR__);
include_once '../EndPointActions.php';
chdir(__DIR__);
include_once '../NoDbEndPoint.php';
chdir(__DIR__);
include_once '../../utils/ServerData.php';
chdir(__DIR__);

class Utils extends NoDbEndPoint implements EndPointActions
{
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response, array('getDate', 'getIp'), strtolower(Utils::class));
    }

    public function perform()
    {
        $request = parent::getRequest();
        $response = parent::getResponse();
        $action = $request->getValue('action');
        switch ($action) {
            case 'getDate':
            {
                $date = (new ServerData())->getDate(true);
                $response->addValue(parent::getEndPoint(), array($action => $date));
                $response->printResponse();
                break;
            }
            case 'getIp':
            {
                $ip = (new ServerData())->getDate(true);
            }
            default:
                $response->printError('The action you want to perform is available, but not implemented', 400);
        }
    }
}