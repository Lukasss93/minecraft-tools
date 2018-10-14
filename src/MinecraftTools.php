<?php

namespace MinecraftTools;

use Exception;
use GuzzleHttp\Client;

class MinecraftTools {
	
	/**
	 * Show the status of Mojang Services
	 * @return array|bool Array of service=>status, FALSE on failure
	 */
	public static function serviceStatuses() {
		
		//initialize client
		$client = new Client();
		
		try {
			//send request
			$response = $client->get('https://status.mojang.com/check');
			
			//get data
			$data = $response->getBody()->getContents();
			
			//decode json
			$data = \GuzzleHttp\json_decode($data, true);
			
			//convert the template
			$services = [];
			foreach($data as $item) {
				$services[array_keys($item)[0]] = array_values($item)[0];
			}
			
			//return the services
			return $services;
		}
		catch(Exception $e) {
			return false;
		}
	}
	
	/**
	 * Get UUID from username
	 * @param string $username Minecraft username
	 * @return string|bool UUID from usename, FALSE on failure
	 */
	public static function getUUID($username) {
		//initialize client
		$client = new Client();
		
		try {
			//send request
			$response = $client->get('https://api.mojang.com/users/profiles/minecraft/' . $username);
			
			if($response->getStatusCode() !== 200) {
				return false;
			}
			
			//get data
			$data = $response->getBody()->getContents();
			
			//decode json
			$data = \GuzzleHttp\json_decode($data, true);
			
			//return the uuid
			return $data['id'];
		}
		catch(Exception $e) {
			return false;
		}
	}
	
	/**
	 * Get username from UUID
	 * @param string $uuid
	 * @return string|bool Username on success, FALSE on failure
	 */
	public static function getUsername($uuid)
	{
		$history = static::getNameHistory($uuid);
		if (is_array($history)) {
			$last = array_pop($history);
			if (is_array($last) && array_key_exists('name', $last)) {
				return $last['name'];
			}
		}
		return false;
	}
	
	/**
	 * Add dashes to an UUID
	 * @param  string $uuid Minecraft UUID
	 * @return string|bool UUID with dashes (36 chars), FALSE on failure
	 */
	public static function formatUUID($uuid) {
		$uuid = static::minifyUUID($uuid);
		if(is_string($uuid)) {
			return substr($uuid, 0, 8) . '-' .
				substr($uuid, 8, 4) . '-' .
				substr($uuid, 12, 4) . '-' .
				substr($uuid, 16, 4) . '-' .
				substr($uuid, 20, 12);
		}
		return false;
	}
	
	/**
	 * Remove dashes from UUID
	 * @param  string $uuid Minecraft UUID
	 * @return string|bool UUID without dashes (32 chars), FALSE on failure
	 */
	public static function minifyUUID($uuid) {
		if(is_string($uuid)) {
			$minified = str_replace('-', '', $uuid);
			if(strlen($minified) === 32) {
				return $minified;
			}
		}
		return false;
	}
	
	/**
	 * Check if string is a valid UUID, with or without dashes
	 * @param string $uuid to check
	 * @return bool Returns TRUE if the UUID is valid, otherwise FALSE
	 */
	public static function isValidUUID($uuid) {
		return is_string(static::minifyUUID($uuid));
	}
	
	/**
	 * Get name history from UUID
	 * @param string $uuid Minecraft UUID
	 * @return array|false Array with his username's history, FALSE on failure
	 */
	public static function getNameHistory($uuid) {
		//initialize client
		$client = new Client();
		
		try {
			//send request
			$response = $client->get('https://api.mojang.com/user/profiles/' . static::minifyUUID($uuid) . '/names');
			
			//get data
			$data = $response->getBody()->getContents();
			
			//decode json
			$data = \GuzzleHttp\json_decode($data, true);
			
			//return the users
			return $data;
		}
		catch(Exception $e) {
			return false;
		}
	}
}