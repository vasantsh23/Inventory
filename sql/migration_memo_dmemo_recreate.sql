-- ============================================================
-- Migration: drop and recreate `memo` and `dmemo` using the exact
-- structure and data from the supplied live-database dumps. This
-- replaces the incremental ALTER-based migration with a clean,
-- guaranteed-correct baseline.
--
-- WARNING: this DROPS the existing memo/dmemo tables first. Any
-- edits made directly in those tables since the last export will be
-- lost — this restores exactly what's in the attached dumps.
--
-- Safe to run once in phpMyAdmin's SQL tab.
-- ============================================================

DROP TABLE IF EXISTS memo;
DROP TABLE IF EXISTS dmemo;

CREATE TABLE memo (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company VARCHAR(150) NOT NULL DEFAULT '',
    address VARCHAR(250) NOT NULL DEFAULT '',
    telno   VARCHAR(50)  NOT NULL DEFAULT '',
    fax     VARCHAR(50)  NOT NULL DEFAULT '',
    gsm     VARCHAR(100) NOT NULL DEFAULT '',
    email   VARCHAR(150) NOT NULL DEFAULT '',
    web     VARCHAR(150) NOT NULL DEFAULT '',
    field1 TEXT NOT NULL,
    field2 TEXT NOT NULL,
    field3 TEXT NOT NULL,
    field4 TEXT NOT NULL,
    field5 TEXT NOT NULL,
    field6 TEXT NOT NULL,
    dated  INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=14;

INSERT INTO memo (id, company, address, telno, fax, gsm, email, web, field1, field2, field3, field4, field5, field6, dated) VALUES
(13, 'Veera Dimon b.v', 'Hoveniersstraat 30, Office 318-319, P.Box - 184, Antwerpen 2018, Belgium.', '(03) 232 - 9747', '(03) 234-9546', '0472-922800 / 0479-262844', 'sales@veeradimon.com', 'www.veeradimon.com', 'Received by', 'For Veera Dimon b.v', ' Receiver''s signature', '', '', 'These goods may only be sold with our authorisation and must be returned upon first request.  The goods are for sale, but ONLY WITH OUR AGREEMENT or have to be returned on request. By signature of this document, the consignee agrees to take full responsibility for the goods as detailed above. Under no circumstances may the goods be further consigned without the prior authorisation of the consigner/owner of the goods. At all times the goods will be under the ownership of Veera Dimon bv.<strong>Discounts will be based on the latest Rapaport list.</strong>', 1549350957);

CREATE TABLE dmemo (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company VARCHAR(150) NOT NULL DEFAULT '',
    address VARCHAR(250) NOT NULL DEFAULT '',
    telno   VARCHAR(50)  NOT NULL DEFAULT '',
    fax     VARCHAR(50)  NOT NULL DEFAULT '',
    gsm     VARCHAR(100) NOT NULL DEFAULT '',
    email   VARCHAR(150) NOT NULL DEFAULT '',
    web     VARCHAR(150) NOT NULL DEFAULT '',
    field1 TEXT NOT NULL,
    field2 TEXT NOT NULL,
    field3 TEXT NOT NULL,
    field4 TEXT NOT NULL,
    field5 TEXT NOT NULL,
    field6 TEXT NOT NULL,
    dated  INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=14;

INSERT INTO dmemo (id, company, address, telno, fax, gsm, email, web, field1, field2, field3, field4, field5, field6, dated) VALUES
(13, 'Veera Dimon FZCO', 'Hoveniersstraat 30, Office 318-319, P.Box - 184, Antwerpen 2018, Belgium.', '(03) 232 - 9747', '(03) 234-9546', '0472-922800 / 0479-262844', 'sales@veeradimon.com', 'www.veeradimon.com', 'Received by', 'For Veera Dimon FZCO', ' Receiver''s signature', ' ', '.', 'These goods may only be sold with our authorisation and must be returned upon first request.  The goods are for sale, but ONLY WITH OUR AGREEMENT or have to be returned on request. By signature of this document, the consignee agrees to take full responsibility for the goods as detailed above. Under no circumstances may the goods be further consigned without the prior authorisation of the consigner/owner of the goods. At all times the goods will be under the ownership of Veera Dimon FZCO.<strong>Discounts will be based on the latest Rapaport list.</strong>', 1549350957);
