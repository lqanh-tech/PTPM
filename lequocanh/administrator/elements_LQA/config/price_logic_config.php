<?php

class PriceLogicConfig 
{

    const AUTO_UPDATE_PRICE_ON_IMPORT = false;
    
    const OVERRIDE_EXISTING_PRICE = false;
    
    const CREATE_PRICE_FROM_IMPORT = true;
    
    const DEFAULT_PROFIT_MARGIN = 20;
    
    const AUTO_APPLY_PROFIT_MARGIN = true;
    
    public static function calculateSellingPrice($importPrice, $profitMargin = null)
    {
        if ($profitMargin === null) {
            $profitMargin = self::DEFAULT_PROFIT_MARGIN;
        }
        
        return $importPrice * (1 + $profitMargin / 100);
    }
    
    public static function shouldUpdateReferencePrice($hasActivePrice = false)
    {
        if (!self::AUTO_UPDATE_PRICE_ON_IMPORT) {
            return false;
        }
        
        if ($hasActivePrice && !self::OVERRIDE_EXISTING_PRICE) {
            return false;
        }
        
        return true;
    }
    
    public static function shouldCreatePriceFromImport()
    {
        return self::CREATE_PRICE_FROM_IMPORT;
    }
    
    public static function getCurrentConfig()
    {
        return [
            'auto_update_price_on_import' => self::AUTO_UPDATE_PRICE_ON_IMPORT,
            'override_existing_price' => self::OVERRIDE_EXISTING_PRICE,
            'create_price_from_import' => self::CREATE_PRICE_FROM_IMPORT,
            'default_profit_margin' => self::DEFAULT_PROFIT_MARGIN,
            'auto_apply_profit_margin' => self::AUTO_APPLY_PROFIT_MARGIN
        ];
    }
}
