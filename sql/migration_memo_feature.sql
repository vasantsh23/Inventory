-- ============================================================
-- Migration: adds the three tables needed for the Memo feature —
-- `customer` (customer list), `memo` and `dmemo` (memo footer/
-- signature text, one used per user level: level 4 -> dmemo,
-- level 5 -> memo). Structure and seed data taken directly from
-- the supplied dumps (ENGINE changed to InnoDB and charset to
-- utf8mb4 to match the rest of this app's schema — safe here since
-- all data is plain text).
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS customer (
    custnm  VARCHAR(100) NOT NULL,
    address VARCHAR(250) NOT NULL DEFAULT '',
    email   VARCHAR(100) NOT NULL DEFAULT '',
    shortnm VARCHAR(10)  NOT NULL DEFAULT '',
    custid  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=112;

INSERT INTO `customer` (`custnm`, `address`, `email`, `shortnm`, `custid`) VALUES
('Select Customer', '', '', 'Test', 17),
('ABC and co', 'test', 'test@gmail.com', 'TST', 18),
('XYZ and co', 'Testadd', 'testxyz@gmail.com', 'XYZ', 27),
('VASANT', 'aaa', 'abc@gmail.com', 'vbggvf', 33),
('ARYAN', 'abc', 'Aaryan@gmail.com', '', 35),
('AARYAN', '903, GOANDEVI hill ,GOANDEVI road Bhandup west', '', '', 37),
('TEST1', 'test1', 'test1@gmail.com', 'test1', 38),
('MANISH JEWELLERS LLC', 'UNIT 2026, BUILDING NO. 2 GOLD AND DIAMOND PARK. AL QUOZ', '', '', 39),
('CARA JEWELLERS FZCO', 'GDP . DUBAI UAE', '', 'CARA JEWEL', 40),
('KGK DIAMOND DMCC', 'UNIT 51 A ALMAS TOWER. JLT. DUBAI. UAE', '', '', 41),
('LUXURY GEMS AND DIAMOND TRADING DMCC', '28 A , ALMAS TOWER. JLT. DUBAI. UAE', '', '', 42),
('BRILLIANT DIAMOND DMCC', 'UNIT 37F ALMAS TOWER. JLT', '', '', 43),
('RAEN FZCO', 'DUBAI UAE. PUNIT :- 0522648372', '', '', 44),
('BAFLEH JEWELLERY LLC', 'Hind plaza 10b, 3rd floor gold Souq. Deira.', '', '', 45),
('BUTI VALLAHDAS MAMIYA JEWELLERY LLC', 'GOLD AND DIAMOND PARK. DUBAI UAE', '', '', 46),
('DIAMOND DEAL LLC', 'OFFICE 103, HIND PLAZA 4B. GOLD SOUQ. DEIRA. DUBAI', '', '', 47),
('AL KANZ JEWELLERY LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', 'AL KANZ JE', 48),
('STARGEMS JEWELLERY DMCC', 'UNIT 31 ALMAS TOWER. JLT', '', 'STARGEMS J', 49),
('AL SAMAKEH DIAMONDS AND JEWELLERY DMCC', ' LAITH. DUBAI. UAE', '', '', 50),
('DHAMANI JEWELS LLC', '', '', '', 51),
('ISHTARA JEWELS LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 52),
('NIVODA DMCC', 'UNIT 49A - 27 ALMAS TOWER. JLT. DUBAI UAE', '', '', 53),
('ZAINA JEWELLERS LLC', 'UNIT 225, GOLD LAND BLDG.,GOLD SOUQ. DEIRA.', '', '', 54),
('NATIONAL JEWELLERY LLC', 'SHOP NO 1 AND 2 GOLD SOUQ. DEIRA DUBAI . UAE', '', '', 55),
('RISING STAR FZCO', 'UNIT 16 G ALMAS TOWER. JLT. DUBAI', '', 'RISING STA', 56),
('HASSAN GEMS LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 57),
('DEEP SHAH', '', '', 'DEEP SHAH', 58),
('YRS DIAM DMCC', '', '', 'YRS DIAM D', 59),
('K S JEWELS LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', 'K S JEWELS', 60),
('SUNNY DIAM LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 61),
('AARAV DIAM LLC', 'GOLD LAND. BLDG., GOLD SOUQ. DEIRA. ', '', 'AARAV DIAM', 62),
('VISHAL GORADIA ; EMID :- 784197884816816', 'Deira gold souk', '', '', 63),
('DHYAN DIAM LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', 'DHYAN DIAM', 64),
('SHASHANK DIAMOND LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 65),
('PRECIOUS GEMS LLC', 'ROYAL DIAMOND BLDG GOLD SOUQ. DEIRA. DUBAI', '', '', 66),
('MOHANLAL VALLABHDAS AND  BROS LLC', 'SHOP NO 5 GOLD SOUQ. DEIRA. DUBAI', '', '', 67),
('NEW VERONA FZCO', '', '', '', 68),
('SOVEREIGN GEMS LLC', '', '', '', 69),
('ELITE STAR FZ-LLC - AMITBHAI 0549984906', '', '', '', 70),
('AMAN DIAMONDS AND JEWELLERY LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 71),
('DEVJI AURUM DMCC', '', '', 'DEVJI AURU', 72),
('TEJORI GEMS LLC', 'GDP . DUBAI UAE', '', 'TEJORI GEM', 73),
('KAIA DIAMONDS FZCO', 'UNIT 8-I SILVER TOWER. JLT DUBAI. UAE', '', 'KAIA DIAMO', 74),
('WAZNI JEWELLERY', 'ABU DHABI. UAE', '', '', 75),
('J MANAK FZCO', 'UNIT 31 H ALMAS TOWER', '', '', 76),
('SAMAY JEWEL FZC', '', '', '', 77),
('PRISTINE JEWELS FZCO', '', '', 'PRISTINE J', 78),
('HRD ANTWERP DMCC', '', '', '', 79),
('S R DIA', '', '', '', 80),
('RENEE INTERNATIONAL DMCC', '', '', '', 81),
('DIAMOND DIRECT LLC', '', '', 'DIAMOND DI', 82),
('VIE JEWELS DMCC', '', '', 'VIE JEWELS', 83),
('DIAMOND PASSION', 'M-47 GREATER KAILASH, NEW DELHI', '', 'DIAMOND PA', 84),
('SAEID GEM GOLD ', '', '', '', 85),
('SAEID GEM GOLD & JEWELLERY TRADING LLC', 'SHOP NO 16. GOLD SOUQ.', '', 'SAEID GEM ', 86),
('AL NOBALA DIAMONDS LLC', '', '', 'AL NOBALA ', 87),
('REWA GEMS FZCO', '26 C ALMAS TOWER', '', 'REWA GEMS ', 88),
('HOUSE OF WINDSOR ', '', '', 'HOUSE OF W', 89),
('NEMI GEMS FZE', 'NEME GEMS FZE', '', '', 90),
('NOVEL FINE JEWELLERY DMCC', '', '', 'NOVEL FINE', 91),
('FUTURE GEMS FZCO', '', '', 'FUTURE GEM', 92),
('FAKIH DIAMONDS DMCC', 'FAKIH DIAMONDS DMCC', '', '', 93),
('AUROSTAR FZCO', 'AUROSTAR FZCO', '', '', 94),
('HIR INVESTMENTS LTD.', '', '', 'HIR INVEST', 95),
('INFINITY DIAMONDS LLC', 'GOLD HOUSE BUILDING . AL RAS . DEIRA.', '', '', 96),
('MONILI JEWELLERS FZCO', '', '', 'MONILI JEW', 97),
('KGK DIAMOND AND JEWELLERY DMCC', '', '', 'KGK DIAMON', 98),
('VRAMS DIAMONDS FZCO', '', '', 'VRAMS DIAM', 99),
('BELADAMAZ JEWELLERY FZCO', '', '', 'BELADAMAZ ', 100),
('YRS DIAM FZCO', '', '', 'YRS DIAM F', 101),
('STARGEMS JEWELLERY FZCO', '', '', 'STARGEMS J', 102),
('CARAT DIAMOND COMPANY FZCO', '40G ALMAS TOWER', '', 'CARAT DIAM', 103),
('NOVEL FINE JEWELLERY FZCO', '', '', 'NOVEL FINE', 104),
('BRIJESH PATEL', '', '', 'BRIJESH PA', 105),
('LUXURY GEMS AND DIAMOND TRADING FZCO', '', '', 'LUXURY GEM', 106),
('VVS DIAMONDS FZCO', '', '', 'VVS DIAMON', 107),
('PRANA DIAM LLC', '', '', 'PRANA DIAM', 108),
('DIAMOND ROCKS TRADING FZCO', '', '', 'DIAMOND RO', 109),
('ARVA DIAMONDS FZCO', '12-C ALMAS TOWER.', '', 'ARVA DIAMO', 110),
('KARATISE FZCO', 'GOLD TOWER AG-07-F', '', 'KARATISE F', 111);

CREATE TABLE IF NOT EXISTS memo (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field1 TEXT NOT NULL,
    field2 TEXT NOT NULL,
    field3 TEXT NOT NULL,
    field4 TEXT NOT NULL,
    field5 TEXT NOT NULL,
    field6 TEXT NOT NULL,
    dated  INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=14;

INSERT INTO memo (id, field1, field2, field3, field4, field5, field6, dated) VALUES
(13, 'Received by', 'For Veera Dimon b.v', ' Receiver''s signature', '', '', 'These goods may only be sold with our authorisation and must be returned upon first request.  The goods are for sale, but ONLY WITH OUR AGREEMENT or have to be returned on request. By signature of this document, the consignee agrees to take full responsibility for the goods as detailed above. Under no circumstances may the goods be further consigned without the prior authorisation of the consigner/owner of the goods. At all times the goods will be under the ownership of Veera Dimon bv.<strong>Discounts will be based on the latest Rapaport list.</strong>', 1549350957);

CREATE TABLE IF NOT EXISTS dmemo (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field1 TEXT NOT NULL,
    field2 TEXT NOT NULL,
    field3 TEXT NOT NULL,
    field4 TEXT NOT NULL,
    field5 TEXT NOT NULL,
    field6 TEXT NOT NULL,
    dated  INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=14;

INSERT INTO dmemo (id, field1, field2, field3, field4, field5, field6, dated) VALUES
(13, 'Received by', 'For Veera Dimon FZCO', ' Receiver''s signature', ' ', '.', 'These goods may only be sold with our authorisation and must be returned upon first request.  The goods are for sale, but ONLY WITH OUR AGREEMENT or have to be returned on request. By signature of this document, the consignee agrees to take full responsibility for the goods as detailed above. Under no circumstances may the goods be further consigned without the prior authorisation of the consigner/owner of the goods. At all times the goods will be under the ownership of Veera Dimon FZCO.<strong>Discounts will be based on the latest Rapaport list.</strong>', 1549350957);
