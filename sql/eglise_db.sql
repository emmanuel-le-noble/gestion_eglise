CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_role VARCHAR(50) NOT NULL
);

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    statut ENUM('actif','inactif') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE membres (
    id INT AUTO_INCREMENT PRIMARY KEY,

    matricule VARCHAR(20) UNIQUE NOT NULL,

    nom VARCHAR(100) NOT NULL,
    prenoms VARCHAR(150) NOT NULL,

    sexe ENUM('Masculin','Feminin') NOT NULL,

    date_naissance DATE,
    lieu_naissance VARCHAR(100),

    profession VARCHAR(100),

    eglise_provenance VARCHAR(150),

    date_arrivee DATE,

    baptise BOOLEAN DEFAULT FALSE,
    date_bapteme DATE NULL,
    lieu_bapteme VARCHAR(150) NULL,

    engagement_moral BOOLEAN DEFAULT FALSE,

    groupe_action ENUM('Hommes','Femmes','Jeunesses','Enfants'),

    qualite ENUM('Membre','Ami','Enfant'),

    statut_membre ENUM(
        'Actif',
        'Inactif',
        'Abandon',
        'Depart'
    ) DEFAULT 'Actif',

    telephone1 VARCHAR(20),
    telephone2 VARCHAR(20) NULL,

    email VARCHAR(100) NULL,

    quartier VARCHAR(100),

    photo VARCHAR(255) NULL,

    commentaire TEXT NULL,

    date_enregistrement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    utilisateur_id INT,

    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE mariages (
    id INT AUTO_INCREMENT PRIMARY KEY,

    membre_id INT NOT NULL,

    nom_conjoint VARCHAR(150),

    date_mariage DATE,

    lieu_mariage VARCHAR(150),

    FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE
);

CREATE TABLE enfants (
    id INT AUTO_INCREMENT PRIMARY KEY,

    membre_id INT NOT NULL,

    nom VARCHAR(100),

    prenoms VARCHAR(150),

    sexe ENUM('Masculin','Feminin'),

    date_naissance DATE,

    FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE
);

CREATE TABLE historique_membre (
    id INT AUTO_INCREMENT PRIMARY KEY,

    membre_id INT NOT NULL,

    type_evenement VARCHAR(100),

    description TEXT,

    date_evenement DATE,

    utilisateur_id INT,

    FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE,

    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE activites (
    id INT AUTO_INCREMENT PRIMARY KEY,

    titre VARCHAR(150) NOT NULL,

    description TEXT,

    observations TEXT,

    date_activite DATE,

    utilisateur_id INT,

    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE photos_activites (
    id INT AUTO_INCREMENT PRIMARY KEY,

    activite_id INT NOT NULL,

    photo VARCHAR(255),

    FOREIGN KEY (activite_id) REFERENCES activites(id) ON DELETE CASCADE
);

CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,

    annee YEAR NOT NULL,

    montant_total DECIMAL(12,2),

    description TEXT
);

CREATE TABLE lignes_budget (
    id INT AUTO_INCREMENT PRIMARY KEY,

    budget_id INT NOT NULL,

    libelle VARCHAR(150),

    montant_prevu DECIMAL(12,2),

    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    type_transaction ENUM('Entree','Sortie'),

    montant DECIMAL(12,2),

    description TEXT,

    date_transaction DATETIME DEFAULT CURRENT_TIMESTAMP,

    ligne_budget_id INT,

    utilisateur_id INT,

    FOREIGN KEY (ligne_budget_id) REFERENCES lignes_budget(id),

    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE comptes_mutuelle (
    id INT AUTO_INCREMENT PRIMARY KEY,

    membre_id INT UNIQUE NOT NULL,

    date_ouverture DATE,

    statut_compte ENUM('Actif','Ferme') DEFAULT 'Actif',

    solde DECIMAL(12,2) DEFAULT 0,

    FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE
);

CREATE TABLE depots (
    id INT AUTO_INCREMENT PRIMARY KEY,

    compte_id INT NOT NULL,

    montant DECIMAL(12,2),

    date_depot DATETIME DEFAULT CURRENT_TIMESTAMP,

    utilisateur_id INT,

    FOREIGN KEY (compte_id) REFERENCES comptes_mutuelle(id) ON DELETE CASCADE,

    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE retraits (
    id INT AUTO_INCREMENT PRIMARY KEY,

    compte_id INT NOT NULL,

    montant DECIMAL(12,2),

    commission DECIMAL(12,2) DEFAULT 0,

    date_retrait DATETIME DEFAULT CURRENT_TIMESTAMP,

    utilisateur_id INT,

    FOREIGN KEY (compte_id) REFERENCES comptes_mutuelle(id) ON DELETE CASCADE,

    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE prets (
    id INT AUTO_INCREMENT PRIMARY KEY,

    compte_id INT NOT NULL,

    montant DECIMAL(12,2),

    date_pret DATE,

    debut_remboursement DATE,

    statut ENUM('En cours','Rembourse') DEFAULT 'En cours',

    FOREIGN KEY (compte_id) REFERENCES comptes_mutuelle(id) ON DELETE CASCADE
);

CREATE TABLE remboursements (
    id INT AUTO_INCREMENT PRIMARY KEY,

    pret_id INT NOT NULL,

    montant DECIMAL(12,2),

    date_remboursement DATETIME DEFAULT CURRENT_TIMESTAMP,

    utilisateur_id INT,

    FOREIGN KEY (pret_id) REFERENCES prets(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE recus (
    id INT AUTO_INCREMENT PRIMARY KEY,

    type_operation VARCHAR(50),

    reference_operation INT,

    numero_recu VARCHAR(50),

    date_generation DATETIME DEFAULT CURRENT_TIMESTAMP
);