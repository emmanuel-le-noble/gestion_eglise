<?php
// gestion_eglise/includes/helpers.php

function genererMatricule($pdo, $date_arrivee = null, $offset = 0) {
    // Si la date est vide, nulle ou non valide, on prend la date du jour actuel
    if (empty($date_arrivee) || $date_arrivee === '0000-00-00') {
        $date_arrivee = date('Y-m-d'); 
    }

    // Méthode de calcul du préfixe basé sur la date d'adhésion
    $timestamp = strtotime($date_arrivee);
    $mois = date('m', $timestamp);   // Ex: 05
    $annee = date('y', $timestamp);  // Ex: 26
    
    $prefixe = 'MEMB-' . $mois . $annee; // Ex: "MEMB-0526"
    
    // OPTIMISATION : On ajoute "FOR UPDATE" pour verrouiller la ligne le temps de la transaction
    // Cela évite que deux lignes générées à la même milliseconde prennent le même numéro
    $stmt = $pdo->prepare("SELECT matricule FROM membres WHERE matricule LIKE ? ORDER BY matricule DESC LIMIT 1 FOR UPDATE");
    $stmt->execute([$prefixe . '-%']);
    $dernierMembre = $stmt->fetch();
    
    if ($dernierMembre) {
        // Extraction du numéro séquentiel (les 4 derniers caractères)
        $dernierNumero = (int)substr($dernierMembre['matricule'], -4);
        $nouveauNumero = $dernierNumero + 1 + $offset; // Application du décalage si nécessaire
    } else {
        // Si c'est le tout premier membre de ce mois
        $nouveauNumero = 1 + $offset;
    }
    
    // On assemble au format : MEMB-MMAA-XXXX (ex: MEMB-0526-0001)
    return $prefixe . '-' . str_pad($nouveauNumero, 4, '0', STR_PAD_LEFT);
}