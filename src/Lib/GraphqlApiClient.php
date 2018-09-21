<?php

namespace Demo\Lib;

use Exception;

function idx($array, $key, $default = null)
{
	if (array_key_exists($key, $array))
	{
		return $array[$key];
	}

	return $default;
}

class GraphqApiClient
{
	private static $endpoint = null;

	public function __construct()
	{
	}

	public static function Version():string
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/GetVersion.graphql"),
		];
		$data = self::getDataFromQuery($query);

		return idx($data, "version", "");
	}

	public static function IsPriviliged():bool
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/IsPriviliged.graphql"),
		];
		$data = self::getDataFromQuery($query);

		if(!$data) return false;

		return idx($data, "isPriviliged", false);
	}

	public static function Login(string $ApiToken, string $ApiPassphrase):string
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/Login.graphql"),
			"variables" =>
			[
				'ApiToken' => $ApiToken,
				'ApiPassphrase' => $ApiPassphrase
			]
		];
		$data = self::getDataFromQuery($query);

		if(!$data) return "";

		return idx($data, "loginApiUser", "");
	}

	public static function AddRecipientsToList(int $listId, $recipientIds)
	{
		$query = [
			"query" => file_get_contents(__DIR__ . "/../ZenterApiQueries/AddRecipientsToList.graphql"),
			"variables" =>
			[
				'id' => $id,
				'recipientIds' => $recipientIds
			]
		];

		$data = self::getDataFromQuery($query);

		if(!$data) return null;

		return idx($data, 'AddRecipientToList');
	}

	public static function Initialize(string $endpoint)
	{
		self::$endpoint = $endpoint;
	}

	public static function GetEndpoint():string
	{
		if(self::$endpoint === null)
		{
			throw new \Exception('Service has not been initialized');
		}
		return self::$endpoint;
	}

	public static function getDataFromQuery(array $query)
	{
		if(self::$endpoint === null)
		{
			throw new \Exception('Service has not been initialized');
		}

		$data = self::getGraphQlResponse($query);
		if(!$data)
		{
			return null;
		}

		if($errors = idx($data, 'errors'))
		{
			error_log(print_r($errors, true));
			throw new Exception('GraphQl Errors! (' . print_r($errors, true) . ')');
		}
		return $data['data'];
	}

	public static function getGraphQlResponse(array $query)
	{
		$query = json_encode($query);
		if($query === false)
		{
			$error = json_last_error_msg();
			throw new Exception('Json Encode Error: '.$error);
		}
		$dataString = self::callEndpointWithCurl(self::getEndpoint(),$query);

		$data = json_decode($dataString, true);
		if($data === false)
		{
			throw new Exception("Invalid data returned from server. (false)");
		}

		return $data;
	}

	public static function callEndpointWithCurl(string $endpoint, string $query)
	{
		$handle = curl_init($endpoint);
		curl_setopt_array($handle, [
			CURLOPT_CUSTOMREQUEST 		=> "POST",
			CURLOPT_POSTFIELDS 			=> $query,
			CURLOPT_RETURNTRANSFER 		=> true,
			CURLOPT_IPRESOLVE 			=> CURL_IPRESOLVE_V4,
			CURLOPT_DNS_CACHE_TIMEOUT 	=> 2,
			CURLOPT_HTTPHEADER 			=> [
		    	'Content-Type: application/json',
		    	'Content-Length: ' . strlen($query)
			]
		]);

		$data = curl_exec($handle);
		$curlError = curl_error($handle);
		$http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		curl_close($handle);


		if($curlError || $http_code !== 200 || $data == "" || $data === null || trim($data) == '')
		{
			error_log("--------START CURL ERROR--------");
			error_log('Endpoint: ' . $endpoint);
			error_log('HttpCode: ' . $http_code);
			error_log('Query: ' . $query);
			error_log('CurlError: ' . $curlError);
			error_log("IncommingData:\n" . $data);
		}

		if(!is_string($data))
		{
			throw new \Exception('Non string returned from curl');
		}

  		return $data;
	}
}
