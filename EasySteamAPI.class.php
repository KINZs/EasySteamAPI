<?php

class SteamAPI
{
    const STEAM_APIKEY = "4B5053C092C383C7270FCBF9A1EBE0B3";  // Get from https://steamcommunity.com/dev/apikey
    const BPTF_APIKEY = "567884cbb98d88453ee94ae0";           // Get from https://backpack.tf/api/market
    const PRICES_LOCATION = "cache/prices.json";              // Where to cache the prices from BP.TF
    const CACHE_LOCATION = "cache/steam64/";                  // Where to store global json files after creation
    
    private function getProfile($steamid){
        $profileJson = json_decode(file_get_contents('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.self::STEAM_APIKEY.'&steamids='.$steamid), true);
        
        if (!$profileJson["response"]["players"] == null){
            $profileArray = array(
                "steamid" => $profileJson["response"]["players"][0]["steamid"],
                "name" => $profileJson["response"]["players"][0]["personaname"],
                "profileurl" => $profileJson["response"]["players"][0]["profileurl"],
                "avatar" => $profileJson["response"]["players"][0]["avatarfull"]
            );
        }
        return $profileArray;
        
    }
    
    private function getCSGOInv($steamid){
        $csgoInvJson = json_decode(file_get_contents('http://steamcommunity.com/profiles/'.$steamid.'/inventory/json/730/2'), true);
        $csgoItemJson = json_decode(file_get_contents('https://api.steampowered.com/IEconItems_730/GetPlayerItems/v1/?key='.self::STEAM_APIKEY.'&format=json&steamid='.$steamid), true);
        $csgoPricesJson = json_decode(file_get_contents('http://jamescullen.me/inventory/api/cache/prices.json'), true);
        
        $invArray = array();

        if (!$csgoInvJson["success"] == null) {
            $keysOfInv = array_keys($csgoInvJson["rgInventory"]);
            $totalItems = sizeof($keysOfInv);
            
            for ($x = 0; $x < $totalItems; $x++) {
                $inv_pos = $csgoInvJson["rgInventory"][$keysOfInv[$x]]["pos"];
                $item_pos = $totalItems - $inv_pos;
                
                $classID = $csgoInvJson["rgInventory"][$keysOfInv[$x]]["classid"];
                $instanceID = $csgoInvJson["rgInventory"][$keysOfInv[$x]]["instanceid"];
                $descriptionsKey = $classID."_".$instanceID;
                
                $item = array(
                    "assetid" => $csgoInvJson["rgInventory"][$keysOfInv[$x]]["id"],
                    "name" => $csgoInvJson["rgDescriptions"][$descriptionsKey]["market_name"],
                    "condition" => $csgoInvJson["rgDescriptions"][$descriptionsKey]["tags"][5]["name"],
                    "rarity" => $csgoInvJson["rgDescriptions"][$descriptionsKey]["tags"][4]["name"],
                    "weapon" => $csgoInvJson["rgDescriptions"][$descriptionsKey]["tags"][1]["name"],
                    "image" => "http://steamcommunity-a.akamaihd.net/economy/image/".$csgoInvJson["rgDescriptions"][$descriptionsKey]["icon_url"],
                    "float" => null,
                    "price" => null,
                    "tradeable" => $csgoInvJson["rgDescriptions"][$descriptionsKey]["tradable"],
                    "inspect" => $csgoInvJson["rgDescriptions"][$descriptionsKey]["actions"][0]["link"]
                );
                
                if(isset($csgoItemJson["result"]["items"][$item_pos]["attributes"][2])){
                    $item["float"] = $csgoItemJson["result"]["items"][$item_pos]["attributes"][2]["float_value"];
                }
                $name = $csgoInvJson["rgDescriptions"][$descriptionsKey]["market_name"];
                if(isset($csgoPricesJson["response"]["items"][$name]["value"])){
                    $item["price"] = $csgoPricesJson["response"]["items"][$name]["value"] / 100; // Show in dollars, not cent
                }
                
                array_push($invArray, $item);
            }
        }
        return $invArray;
    }
    
    public function getCSGOPrices(){
    	$pricesBPTF = file_get_contents('http://backpack.tf/api/IGetMarketPrices/v1/?key='.self::BPTF_APIKEY.'&appid=730');
    	file_put_contents(self::PRICES_LOCATION,$pricesBPTF);
    }
    
    
    private function getRustInv($steamid){
        $rustInvJson = json_decode(file_get_contents('http://steamcommunity.com/profiles/'.$steamid.'/inventory/json/252490/2'), true);
        $invArray = array();
        foreach ($rustInvJson["rgDescriptions"] as $item){
          $new_item = array(
            "name" => $item["name"],
            "img" => "http://steamcommunity-a.akamaihd.net/economy/image/".$item["icon_url_large"],
            "tradable" => $item["tradable"]
          );
          array_push($invArray, $new_item);
        }
        return $invArray;
    }
    
    public function newJson($steamid){
        
        $profile = $this->getProfile($steamid);
        $csgo = $this->getCSGOInv($steamid);
        $rust = $this->getRustInv($steamid);
        
        $arr = array(
            "success" => "true",
            "timestamp" => time(),
            "profile" => $profile,
            "csgo" => $csgo,
            "rust" => $rust
        );
        return $arr;
    }
    
    public function saveJson($user, $json){
        $json = json_encode($json);
        $loc = self::CACHE_LOCATION . $user . ".json";
        file_put_contents($loc, $json);
        return array(
            "success" => "true"    
        );
    }
    
    public function getJson($steamid){
        $loc = self::CACHE_LOCATION . $steamid . ".json";
        if(file_exists($loc)){
            return file_get_contents($loc);
        }
        else {
            $save = $this->saveJson($steamid, $this->newJson($steamid));
            if ($save->success == "true"){
                return file_get_contents($loc);
            }
        }
        
    }
    
    
}