<?php
/**
 * Configuration de la tarification énergétique
 */

class PricingConfig {
    // Prix par kWh pour l'énergie injectée (ce que la maison reçoit)
    private $price_per_kwh_injected = 0.15; // 0.15 € par kWh
    
    // Prix par kWh pour l'énergie soutirée (ce que la maison paie)
    private $price_per_kwh_taken = 0.20; // 0.20 € par kWh
    
    // TVA applicable
    private $vat_rate = 0.20; // 20%
    
    /**
     * Obtenir le prix par kWh pour l'énergie injectée
     */
    public function getPriceInjected() {
        return $this->price_per_kwh_injected;
    }
    
    /**
     * Obtenir le prix par kWh pour l'énergie soutirée
     */
    public function getPriceTaken() {
        return $this->price_per_kwh_taken;
    }
    
    /**
     * Obtenir le taux de TVA
     */
    public function getVatRate() {
        return $this->vat_rate;
    }
    
    /**
     * Calculer le montant pour l'énergie injectée (crédit)
     */
    public function calculateInjectedAmount($energy_kwh) {
        return round($energy_kwh * $this->price_per_kwh_injected, 2);
    }
    
    /**
     * Calculer le montant pour l'énergie soutirée (débit)
     */
    public function calculateTakenAmount($energy_kwh) {
        return round($energy_kwh * $this->price_per_kwh_taken, 2);
    }
    
    /**
     * Calculer le montant net à payer pour une maison
     * Montant positif = la maison doit payer
     * Montant négatif = la maison reçoit de l'argent
     */
    public function calculateNetAmount($energy_injected, $energy_taken) {
        $credit = $this->calculateInjectedAmount($energy_injected);
        $debit = $this->calculateTakenAmount($energy_taken);
        
        $net_amount = $debit - $credit;
        
        return round($net_amount, 2);
    }
    
    /**
     * Calculer le montant avec TVA
     */
    public function calculateWithVAT($amount) {
        return round($amount * (1 + $this->vat_rate), 2);
    }
    
    /**
     * Obtenir un résumé de facturation
     */
    public function getBillingSummary($energy_injected, $energy_taken) {
        $credit = $this->calculateInjectedAmount($energy_injected);
        $debit = $this->calculateTakenAmount($energy_taken);
        $net_amount = $debit - $credit;
        $vat = round($net_amount * $this->vat_rate, 2);
        $total_with_vat = $net_amount + $vat;
        
        return [
            'energy_injected' => round($energy_injected, 2),
            'energy_taken' => round($energy_taken, 2),
            'credit_amount' => $credit,
            'debit_amount' => $debit,
            'net_amount' => round($net_amount, 2),
            'vat_amount' => $vat,
            'total_amount' => round($total_with_vat, 2)
        ];
    }
}
?>