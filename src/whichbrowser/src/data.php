<?php

	namespace WhichBrowser\Data;
	
	use WhichBrowser\Version as Version;
	

	class BrowserIds {
		static $ANDROID_BROWSERS = [];

		static function identify($type, $model) {
			require_once __DIR__ . '/../data/id-' . $type . '.php';

			switch($type) {
				case 'android':		return BrowserIds::identifyList(BrowserIds::$ANDROID_BROWSERS, $model);
			}

			return false;
		}

		static function identifyList($list, $id) {
			if (isset($list[$id])) {
				return $list[$id];
			}

			return false;
		}
	}

	class BuildIds {
		static $ANDROID_BUILDS = [];

		static function identify($type, $id) {
			require_once __DIR__ . '/../data/build-' . $type . '.php';

			switch($type) {
				case 'android':		return BuildIds::identifyList(BuildIds::$ANDROID_BUILDS, $id);
			}

			return false;
		}

		static function identifyList($list, $id) {
			if (isset($list[$id])) {
				if (is_array($list[$id]))
					return new Version($list[$id]);
				else
					return new Version([ 'value' => $list[$id] ]);
			}

			return false;
		}
	}

	class Manufacturers {
		static $TELEVISION = [];

		static function identify($type, $name) {
			$name = preg_replace('/^CUS\:/u', '', trim($name));

			require_once __DIR__ . '/../data/manufacturers.php';

			if (isset(Manufacturers::$TELEVISION[$name])) return Manufacturers::$TELEVISION[$name];
			return $name;
		}
	}

	class DeviceModels {
		static $ANDROID_MODELS = [];
		static $ASHA_MODELS = [];
		static $BADA_MODELS = [];
		static $BREW_MODELS = [];
		static $FIREFOXOS_MODELS = [];
		static $TIZEN_MODELS = [];
		static $TOUCHWIZ_MODELS = [];
		static $WINDOWS_MOBILE_MODELS = [];
		static $WINDOWS_PHONE_MODELS = [];
		static $PALMOS_MODELS = [];
		static $S30_MODELS = [];
		static $S40_MODELS = [];
		static $S60_MODELS = [];
		static $FEATURE_MODELS = [];
		static $BLACKBERRY_MODELS = [];
		static $IOS_MODELS = [];


		static function identify($type, $model) {
			require_once __DIR__ . '/../data/models-' . $type . '.php';

			switch($type) {
				case 'android':		return DeviceModels::identifyAndroid($model);
				case 'asha': 		return DeviceModels::identifyList(DeviceModels::$ASHA_MODELS, $model);
				case 'bada': 		return DeviceModels::identifyList(DeviceModels::$BADA_MODELS, $model);
				case 'blackberry':	return DeviceModels::identifyBlackBerry($model);
				case 'brew': 		return DeviceModels::identifyList(DeviceModels::$BREW_MODELS, $model);
				case 'firefoxos': 	return DeviceModels::identifyList(DeviceModels::$FIREFOXOS_MODELS, $model, false);
				case 'ios':			return DeviceModels::identifyIOS($model);
				case 'tizen': 		return DeviceModels::identifyList(DeviceModels::$TIZEN_MODELS, $model);
				case 'touchwiz': 	return DeviceModels::identifyList(DeviceModels::$TOUCHWIZ_MODELS, $model);
				case 'wm': 			return DeviceModels::identifyList(DeviceModels::$WINDOWS_MOBILE_MODELS, $model);
				case 'wp': 			return DeviceModels::identifyList(DeviceModels::$WINDOWS_PHONE_MODELS, $model);
				case 's30': 		return DeviceModels::identifyList(DeviceModels::$S30_MODELS, $model);
				case 's40': 		return DeviceModels::identifyList(DeviceModels::$S40_MODELS, $model);
				case 's60': 		return DeviceModels::identifyList(DeviceModels::$S60_MODELS, $model);
				case 'palmos': 		return DeviceModels::identifyList(DeviceModels::$PALMOS_MODELS, $model);
				case 'feature': 	return DeviceModels::identifyList(DeviceModels::$FEATURE_MODELS, $model);
			}

			return (object) [ 'type' => '', 'model' => $model, 'identified' => ID_NONE ];
		}

		static function identifyIOS($model) {
			$model = str_replace('Unknown ', '', $model);
			$model = preg_replace("/iPh([0-9],[0-9])/", 'iPhone\\1', $model);
			$model = preg_replace("/iPd([0-9],[0-9])/", 'iPod\\1', $model);

			return DeviceModels::identifyList(DeviceModels::$IOS_MODELS, $model);
		}

		static function identifyAndroid($model) {
			$result = DeviceModels::identifyList(DeviceModels::$ANDROID_MODELS, $model);

			if (!$result->identified) {
				$model = DeviceModels::cleanup($model);
				if (preg_match('/AndroVM/iu', $model)  || $model == 'Emulator' || $model == 'x86 Emulator' || $model == 'x86 VirtualBox' || $model == 'vm') {
					return (object) [
						'type'			=> TYPE_EMULATOR,
						'identified'	=> ID_PATTERN,
						'manufacturer'	=> null,
						'model'			=> null,
						'generic'		=> false
					];
				}
			}

			return $result;
		}

		static function identifyBlackBerry($model) {
			$device = (object) [
				'type'			=> TYPE_MOBILE,
				'identified'	=> ID_PATTERN,
				'manufacturer'	=> 'RIM',
				'model'			=> 'BlackBerry ' . $model,
				'generic'		=> false
			];

			if (isset(DeviceModels::$BLACKBERRY_MODELS[$model])) {
				$device->model = 'BlackBerry ' . DeviceModels::$BLACKBERRY_MODELS[$model] . ' ' . $model;
				$device->identified |= ID_MATCH_UA;
			}

			return $device;
		}

		static function identifyList($list, $model, $cleanup = true) {
			$original = $model;

			if ($cleanup) $model = DeviceModels::cleanup($model);

			$device = (object) [
				'type'			=> TYPE_MOBILE,
				'identified'	=> ID_NONE,
				'manufacturer'	=> null,
				'model'			=> $model,
				'identifier'	=> $original,
				'generic'		=> false
			];

			foreach ($list as $m => $v) {
				$match = null;

				if (DeviceModels::hasMatch($m, $model)) {
					if (substr($m, -2) == "!!") {
						foreach ($v as $m2 => $v2) {
							if (DeviceModels::hasMatch($m2, $model)) {
								$match = $v2;
								continue;
							}
						}
					}
					else {
						$match = $v;
					}
				}

				if ($match) {
 					$device->manufacturer = $match[0];
					$device->model = $match[1];
					if (isset($match[2])) $device->type = $match[2];
					if (isset($match[3])) $device->flag = $match[3];
					$device->identified = ID_MATCH_UA;

					if ($device->manufacturer == null && $device->model == null) {
						$device->identified = ID_PATTERN;
					}

					return $device;
				}
			}

			return $device;
		}

		static function hasMatch($pattern, $model) {
			if (substr($pattern, -2) == "!!")
				return !! preg_match('/^' . substr($pattern, 0, -2) . '/iu', $model);
			else if (substr($pattern, -1) == "!")
				return !! preg_match('/^' . substr($pattern, 0, -1) . '/iu', $model);
			else
				return strtolower($pattern) == strtolower($model);
		}

		static function cleanup($s = '') {
			$s = preg_replace('/^phone\//', '', $s);
			$s = preg_replace('/\/[^\/]+$/u', '', $s);
			$s = preg_replace('/\/[^\/]+ Android\/.*/u', '', $s);

			$s = preg_replace('/UCBrowser$/u', '', $s);

			$s = preg_replace('/_TD$/u', '', $s);
			$s = preg_replace('/_LTE$/u', '', $s);
			$s = preg_replace('/_CMCC$/u', '', $s);
			$s = preg_replace('/_CUCC$/u', '', $s);
			$s = preg_replace('/-BREW.+$/u', '', $s);
			$s = preg_replace('/ MIDP.+$/u', '', $s);

			$s = preg_replace('/_/u', ' ', $s);
			$s = preg_replace('/^\s+|\s+$/u', '', $s);

			$s = preg_replace('/^tita on /u', '', $s);
			$s = preg_replace('/^De-Sensed /u', '', $s);
			$s = preg_replace('/^ICS AOSP on /u', '', $s);
			$s = preg_replace('/^Baidu Yi on /u', '', $s);
			$s = preg_replace('/^Buildroid for /u', '', $s);
			$s = preg_replace('/^Gingerbread on /u', '', $s);
			$s = preg_replace('/^Android (on |for )/u', '', $s);
			$s = preg_replace('/^Generic Android on /u', '', $s);
			$s = preg_replace('/^Full JellyBean( on )?/u', '', $s);
			$s = preg_replace('/^Full (AOSP on |Android on |Base for |Cappuccino on |MIPS Android on |Webdroid on |JellyBean on |Android)/u', '', $s);
			$s = preg_replace('/^AOSPA? on /u', '', $s);

			$s = preg_replace('/^Acer( |-)?/iu', '', $s);
			$s = preg_replace('/^Iconia( Tab)? /u', '', $s);
			$s = preg_replace('/^ASUS ?/u', '', $s);
			$s = preg_replace('/^Ainol /u', '', $s);
			$s = preg_replace('/^Coolpad ?/iu', 'Coolpad ', $s);
			$s = preg_replace('/^Alcatel[_ ]OT[_-](.*)/iu', 'One Touch $1', $s);
			$s = preg_replace('/^ALCATEL /u', '', $s);
			$s = preg_replace('/^YL-/u', '', $s);
			$s = preg_replace('/^TY-K[_\- ]Touch/iu', 'K-Touch', $s);
			$s = preg_replace('/^K-Touch[_\-]/u', 'K-Touch ', $s);
			$s = preg_replace('/^Novo7 ?/iu', 'Novo7 ', $s);
			$s = preg_replace('/^HW-HUAWEI/u', 'HUAWEI', $s);
			$s = preg_replace('/^Huawei[ -]/iu', 'Huawei ', $s);
			$s = preg_replace('/^SAMSUNG SAMSUNG-/iu', '', $s);
			$s = preg_replace('/^SAMSUNG[ -]/iu', '', $s);
			$s = preg_replace('/^(Sony ?Ericsson|Sony)/u', '', $s);
			$s = preg_replace('/^(Lenovo Lenovo|LNV-Lenovo|LENOVO-Lenovo)/u', 'Lenovo', $s);
			$s = preg_replace('/^Lenovo-/u', 'Lenovo', $s);
			$s = preg_replace('/^ZTE-/u', 'ZTE ', $s);
			$s = preg_replace('/^(LG)[ _\/]/u', '$1-', $s);
			$s = preg_replace('/^(HTC.+)\s[v|V][0-9.]+$/u', '$1', $s);
			$s = preg_replace('/^(HTC)[-\/]/u', '$1', $s);
			$s = preg_replace('/^(HTC)([A-Z][0-9][0-9][0-9])/u', '$1 $2', $s);
			$s = preg_replace('/^(Motorola[\s|-])/u', '', $s);
			$s = preg_replace('/^(MOT-)/u', '', $s);
			$s = preg_replace('/^Moto([^\s])/u', '$1', $s);

			$s = preg_replace('/-?(orange(-ls)?|vodafone|bouygues|parrot|Kust|ls)$/iu', '', $s);
			$s = preg_replace('/http:\/\/.+$/iu', '', $s);
			$s = preg_replace('/^\s+|\s+$/u', '', $s);

			return $s;
		}
	}


	class DeviceProfiles {
		static $PROFILES = [];

		static function identify($url) {
			require_once __DIR__ . '/../data/profiles.php';

			if (isset(DeviceProfiles::$PROFILES[$url])) {
				return DeviceProfiles::$PROFILES[$url];
			}

			return false;
		}
	}
