-- ============================================================
-- Migration: add company, address, telno, fax, gsm, email, web
-- columns to `memo` and `dmemo` — these drive the memo masthead
-- (company block on the right, and the "Firm X" name in the body
-- text), replacing the previous use of the general Site Setup table
-- for this purpose.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE memo
    ADD COLUMN company VARCHAR(150) NOT NULL DEFAULT '' AFTER id,
    ADD COLUMN address VARCHAR(250) NOT NULL DEFAULT '' AFTER company,
    ADD COLUMN telno   VARCHAR(50)  NOT NULL DEFAULT '' AFTER address,
    ADD COLUMN fax     VARCHAR(50)  NOT NULL DEFAULT '' AFTER telno,
    ADD COLUMN gsm     VARCHAR(100) NOT NULL DEFAULT '' AFTER fax,
    ADD COLUMN email   VARCHAR(150) NOT NULL DEFAULT '' AFTER gsm,
    ADD COLUMN web     VARCHAR(150) NOT NULL DEFAULT '' AFTER email;

ALTER TABLE dmemo
    ADD COLUMN company VARCHAR(150) NOT NULL DEFAULT '' AFTER id,
    ADD COLUMN address VARCHAR(250) NOT NULL DEFAULT '' AFTER company,
    ADD COLUMN telno   VARCHAR(50)  NOT NULL DEFAULT '' AFTER address,
    ADD COLUMN fax     VARCHAR(50)  NOT NULL DEFAULT '' AFTER telno,
    ADD COLUMN gsm     VARCHAR(100) NOT NULL DEFAULT '' AFTER fax,
    ADD COLUMN email   VARCHAR(150) NOT NULL DEFAULT '' AFTER gsm,
    ADD COLUMN web     VARCHAR(150) NOT NULL DEFAULT '' AFTER email;

-- Seed the existing row(s) with values matching your reference memo,
-- so the masthead has real content immediately — edit these anytime
-- via the Memo Footer CRUD screens.
UPDATE memo SET
    company = 'Veera Dimon b.v',
    address = 'Hoveniersstraat 30, Office 318-319, P.Box - 184, Antwerpen 2018, Belgium.',
    telno   = '(03) 232 - 9747',
    fax     = '(03) 234-9546',
    gsm     = '0472-922800 / 0479-262844',
    email   = 'sales@veeradimon.com',
    web     = 'www.veeradimon.com'
WHERE id = (SELECT id FROM (SELECT id FROM memo ORDER BY id DESC LIMIT 1) t);

UPDATE dmemo SET
    company = 'Veera Dimon FZCO',
    address = 'Hoveniersstraat 30, Office 318-319, P.Box - 184, Antwerpen 2018, Belgium.',
    telno   = '(03) 232 - 9747',
    fax     = '(03) 234-9546',
    gsm     = '0472-922800 / 0479-262844',
    email   = 'sales@veeradimon.com',
    web     = 'www.veeradimon.com'
WHERE id = (SELECT id FROM (SELECT id FROM dmemo ORDER BY id DESC LIMIT 1) t);
