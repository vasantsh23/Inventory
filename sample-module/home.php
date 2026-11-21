<?php
declare(strict_types=1);
// The home page (index.php now goes straight to Diamond Jewellery / login).
require_once __DIR__ . '/includes/sample_auth.php';

// Diamond Jewellery and Inventory are for signed-in users only: when
// nobody is signed in these links open the login screen, which then
// continues to the chosen page.
$user          = sample_current_user();
$linkJewellery = sample_member_link('/cat/filter.php');
$linkInventory = main_app_url('/inventory.php'); // main app: asks for a login itself, routes Admins to the Admin module
$linkLogin     = main_app_url('/login.php');
$linkLogout    = main_app_url('/logout.php');
$h = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orion Snow | Your Trusted Partner for Lab-Grown Diamonds</title>
<meta name="description" content="Orion Snow is a B2B supplier of certified lab-grown diamonds, precision-calibrated melees and lab-grown diamond jewellery. Fast, reliable delivery and no minimum order.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,300;0,6..72,400;0,6..72,600;0,6..72,700;1,6..72,400&family=Red+Hat+Display:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --navy: #0F2A4B;
    --blue: #245298;
    --cream: #FAF2E8;
    --card: #FFF5E9;
    --gold: #D6BC8B;
    --line: #EAE8E6;
    --white: #fff;
    --serif: "Newsreader", Georgia, "Times New Roman", serif;
    --sans: "Red Hat Display", "Helvetica Neue", Arial, sans-serif;
    --gutter: 40px;
    --max: 1350px;
    --header-h: 78px;
  }
  *, *::before, *::after { box-sizing: border-box; }
  html { scroll-behavior: smooth; scroll-padding-top: var(--header-h); }
  body {
    margin: 0;
    background: var(--white);
    color: var(--navy);
    font-family: var(--serif);
    font-size: 16px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    overflow-x: hidden;
  }
  img { display: block; max-width: 100%; height: auto; }
  a { color: inherit; }
  h1, h2, h3, p { margin: 0; }
  .container { width: 100%; max-width: calc(var(--max) + var(--gutter) * 2); margin: 0 auto; padding: 0 var(--gutter); }
  .eyebrow { font-family: var(--sans); font-weight: 500; text-transform: uppercase; letter-spacing: .03em; }
  .kicker {
    display: block; font-family: var(--sans); font-weight: 500; font-size: 12px; letter-spacing: .16em;
    text-transform: uppercase; color: var(--blue); margin-bottom: 16px;
  }
  :focus-visible { outline: 2px solid var(--blue); outline-offset: 3px; }

  /* ---------- Buttons ---------- */
  .btn {
    display: inline-block; padding: 13px 32px; min-width: 179px; text-align: center;
    font-family: var(--sans); font-size: 13px; font-weight: 500; letter-spacing: .12em; text-transform: uppercase;
    background: var(--blue); color: var(--white); text-decoration: none; border: 1px solid var(--blue);
    transition: background .2s, color .2s, border-color .2s;
  }
  .btn:hover { background: var(--navy); border-color: var(--navy); }
  .btn-light { background: var(--white); color: var(--navy); border-color: var(--white); }
  .btn-light:hover { background: var(--cream); border-color: var(--cream); color: var(--navy); }
  .btn-outline { background: transparent; color: var(--white); border-color: rgba(255,255,255,.7); }
  .btn-outline:hover { background: rgba(255,255,255,.12); border-color: var(--white); }

  /* ---------- Header ---------- */
  .site-header { position: sticky; top: 0; z-index: 50; background: var(--white); border-bottom: 1px solid var(--line); }
  .header-inner { height: var(--header-h); display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; }
  .main-nav ul { list-style: none; margin: 0; padding: 0; display: flex; gap: 16px; }
  .main-nav a { text-decoration: none; font-size: 14px; color: rgba(15,42,75,.7); white-space: nowrap; transition: color .2s; }
  .main-nav a:hover, .main-nav a[aria-current="page"] { color: var(--blue); }
  .brand img { width: 225px; }
  .header-actions { justify-self: end; display: flex; align-items: center; gap: 4px; }
  .header-link { text-decoration: none; font-size: 16px; padding: 10px 14px; white-space: nowrap; }
  .header-link:hover { color: var(--blue); }
  .icon-btn {
    width: 44px; height: 44px; display: inline-grid; place-items: center;
    background: none; border: 0; padding: 0; cursor: pointer; border-radius: 50%;
  }
  .icon-btn:hover { background: var(--cream); }
  .menu-toggle { display: none; }
  .header-login {
    display: inline-block; margin-left: 8px; padding: 9px 20px; white-space: nowrap;
    font-family: var(--sans); font-size: 12px; font-weight: 500; letter-spacing: .12em; text-transform: uppercase;
    color: var(--white); background: var(--blue); border: 1px solid var(--blue); text-decoration: none;
    transition: background .2s, border-color .2s, color .2s;
  }
  .header-login:hover { background: var(--navy); border-color: var(--navy); }
  .header-login.is-signed-in { color: var(--blue); background: transparent; }
  .header-login.is-signed-in:hover { color: var(--white); background: var(--navy); }
  .header-user { font-family: var(--sans); font-size: 12px; color: rgba(15,42,75,.6); padding: 0 6px; white-space: nowrap; }

  /* Drawer */
  .drawer { position: fixed; inset: 0; z-index: 60; visibility: hidden; }
  .drawer.open { visibility: visible; }
  .drawer-backdrop { position: absolute; inset: 0; background: rgba(15,42,75,.4); opacity: 0; transition: opacity .3s; }
  .drawer.open .drawer-backdrop { opacity: 1; }
  .drawer-panel {
    position: absolute; inset: 0 auto 0 0; width: min(320px, 85vw); overflow-y: auto;
    background: var(--white); padding: 24px; transform: translateX(-100%); transition: transform .3s ease;
    display: flex; flex-direction: column; gap: 24px;
  }
  .drawer.open .drawer-panel { transform: none; }
  .drawer-head { display: flex; align-items: center; justify-content: space-between; }
  .drawer-head img { width: 160px; }
  .drawer nav ul { list-style: none; margin: 0; padding: 0; }
  .drawer nav a { display: block; padding: 14px 0; border-bottom: 1px solid var(--line); text-decoration: none; font-size: 20px; }
  .drawer nav a[aria-current="page"] { color: var(--blue); }

  /* ---------- Hero ---------- */
  .hero {
    position: relative; overflow: hidden; color: var(--white);
    min-height: clamp(600px, 57.5vw, 822px); display: flex; align-items: flex-end;
  }
  .hero > picture { position: absolute; inset: 0; }
  .hero > picture img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
  .hero::after {
    content: ""; position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(15,42,75,.78) 0%, rgba(15,42,75,.35) 45%, transparent 75%),
                linear-gradient(to right, rgba(15,42,75,.35) 0%, transparent 60%);
  }
  .hero-content { position: relative; z-index: 1; padding-top: 120px; padding-bottom: clamp(48px, 6vw, 96px); }
  .hero-badge {
    display: inline-block; padding: 6px 14px; border: 1px solid rgba(255,255,255,.55); border-radius: 999px;
    font-family: var(--sans); font-size: 12px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase;
  }
  .hero h1 { max-width: 720px; margin-top: 22px; font-weight: 400; font-size: clamp(36px, 3.9vw, 56px); line-height: 1.1; }
  .hero .tagline { margin-top: 16px; font-size: 20px; font-weight: 600; color: var(--gold); }
  .hero p.lead { max-width: 600px; margin-top: 14px; font-size: 18px; line-height: 1.5; color: rgba(255,255,255,.92); }
  .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }

  /* ---------- Intro ---------- */
  .intro { padding: 100px 0; }
  .intro .container { display: flex; align-items: center; justify-content: space-between; gap: 60px; }
  .intro h2 {
    flex: 1; max-width: 640px; margin: 0 auto; text-align: center;
    font-size: clamp(28px, 2.5vw, 36px); line-height: 1.25; font-weight: 400; color: var(--blue);
  }
  .intro h2 span { color: var(--navy); font-weight: 300; }
  .intro .intro-logo { width: min(464px, 38%); opacity: .9; margin-right: 40px; }

  /* ---------- Highlights band ---------- */
  .highlights { background: var(--blue); color: rgba(255,255,255,.92); padding: 64px 0; }
  .highlights ul { list-style: none; margin: 0; display: grid; grid-template-columns: repeat(5, 1fr); gap: 32px; }
  .highlights li { text-align: center; font-size: 16px; line-height: 1.4; }
  .highlights li::before {
    content: ""; display: block; width: 10px; height: 10px; margin: 0 auto 16px;
    border: 1px solid var(--gold); transform: rotate(45deg);
  }
  .highlights strong {
    display: block; margin-bottom: 6px; font-family: var(--sans); font-weight: 500; font-size: 15px;
    letter-spacing: .06em; text-transform: uppercase; color: var(--white);
  }

  /* ---------- Section titles ---------- */
  .section-head { text-align: center; max-width: 720px; margin: 0 auto; }
  .section-title { color: var(--blue); font-weight: 300; font-size: clamp(34px, 3.4vw, 48px); line-height: 1.15; letter-spacing: .01em; }
  .section-head .lead { margin-top: 24px; opacity: .85; }

  /* ---------- Portfolio ---------- */
  .portfolio { padding: 100px 0 80px; }
  .offer-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; max-width: 1215px; margin: 70px auto 0; }
  .offer-card { background: var(--cream); padding: 51px 44px; min-height: 278px; transition: background .3s, color .3s; scroll-margin-top: calc(var(--header-h) + 24px); }
  .offer-card .num { font-family: var(--sans); font-size: 12px; letter-spacing: .16em; color: var(--blue); transition: color .3s; }
  .offer-card h3 { margin-top: 18px; font-family: var(--sans); font-weight: 500; font-size: 19px; line-height: 1.3; letter-spacing: .05em; text-transform: uppercase; }
  .offer-card p { margin-top: 18px; font-size: 15px; line-height: 1.6; opacity: .8; }
  .offer-card:hover { background: var(--navy); color: var(--white); }
  .offer-card:hover .num { color: var(--gold); }

  /* ---------- Gallery carousel ---------- */
  .gallery { position: relative; padding: 0 0 48px; }
  .gallery-track {
    display: flex; gap: 13px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth;
    padding: 0 var(--gutter); scrollbar-width: none;
  }
  .gallery-track::-webkit-scrollbar { display: none; }
  .gallery-track img {
    flex: 0 0 auto; width: clamp(260px, 29.7vw, 425px); aspect-ratio: 425 / 533; object-fit: cover; scroll-snap-align: start;
    scroll-margin-left: var(--gutter);
  }
  .gallery-nav {
    position: absolute; top: calc(50% - 24px); transform: translateY(-50%);
    width: 44px; height: 44px; border-radius: 50%; border: 0; cursor: pointer;
    background: rgba(255,255,255,.85); display: grid; place-items: center; box-shadow: 0 2px 10px rgba(15,42,75,.18);
    transition: opacity .2s;
  }
  .gallery-nav.prev { left: 20px; }
  .gallery-nav.next { right: 20px; }
  .gallery-nav[disabled] { opacity: 0; pointer-events: none; }
  .gallery-nav svg { width: 20px; height: 20px; }

  /* ---------- Why Orion Snow ---------- */
  .why { background: var(--cream); padding: 110px 0; }
  .why-grid { display: grid; grid-template-columns: 5fr 7fr; gap: 80px; align-items: start; }
  .why-media { position: sticky; top: calc(var(--header-h) + 32px); }
  .why-media img { width: 100%; aspect-ratio: 425 / 533; object-fit: cover; }
  .why h2 { text-align: left; }
  .why-intro p { margin-top: 18px; font-size: 17px; }
  .features { display: grid; grid-template-columns: 1fr 1fr; gap: 36px 40px; margin-top: 48px; }
  .feature { border-top: 1px solid rgba(36,82,152,.25); padding-top: 20px; }
  .feature h3 { font-size: 20px; font-weight: 600; line-height: 1.2; color: var(--blue); }
  .feature p { margin-top: 10px; font-size: 15px; opacity: .85; }

  /* ---------- Philosophy ---------- */
  .philosophy { padding: 121px 0 125px; }
  .philosophy .container { display: flex; align-items: center; justify-content: space-between; gap: 60px; }
  .philosophy .philosophy-logo { width: min(590px, 44%); opacity: .9; margin-left: 69px; }
  .philosophy-text { max-width: 520px; margin-right: 67px; }
  .philosophy-text h2 { font-style: italic; font-weight: 400; font-size: clamp(30px, 2.6vw, 38px); line-height: 1.15; color: var(--blue); }
  .philosophy-text p { margin-top: 18px; }

  /* ---------- Process timeline ---------- */
  .process { padding: 20px 0 120px; }
  .timeline { position: relative; margin: 60px auto 0; padding: 0 0 0 49px; max-width: 793px; }
  .steps { list-style: none; margin: 0; padding: 0; }
  .timeline::before { content: ""; position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: var(--blue); opacity: .25; }
  .timeline-progress { position: absolute; left: 7px; top: 0; width: 2px; height: 0; background: var(--blue); transition: height .4s ease; }
  .step { position: relative; margin-bottom: 60px; }
  .step:last-child { margin-bottom: 0; }
  .step::before {
    content: ""; position: absolute; left: -49px; top: 8px; width: 16px; height: 16px; border-radius: 50%;
    background: var(--cream); border: 3px solid var(--cream); transition: background .3s, border-color .3s;
  }
  .step-card { background: var(--card); border-radius: 8px; padding: 24px; opacity: .55; transition: opacity .4s ease; }
  .step.active::before { background: var(--blue); border-color: var(--blue); }
  .step.active .step-card { opacity: 1; }
  .step-label { font-family: var(--sans); font-size: 12px; font-weight: 500; letter-spacing: .14em; color: var(--blue); line-height: 1.4; }
  .step h3 { margin-top: 8px; font-size: 22px; font-weight: 600; line-height: 1.15; color: var(--blue); }
  .step p { margin-top: 12px; color: var(--blue); line-height: 1.6; }

  /* ---------- Final CTA ---------- */
  .cta { background: var(--navy); color: var(--white); padding: 110px 0; text-align: center; }
  .cta h2 { font-weight: 300; font-size: clamp(38px, 3.6vw, 52px); line-height: 1.1; }
  .cta .tagline { margin-top: 16px; font-style: italic; font-size: clamp(20px, 1.8vw, 26px); color: var(--gold); }
  .cta p.body { max-width: 620px; margin: 24px auto 0; font-size: 17px; color: rgba(255,255,255,.85); }
  .cta .btn { margin-top: 36px; }

  /* ---------- More information ---------- */
  .more-info { background: var(--cream); padding: 100px 0; }
  .more-info .container { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 40px; }
  .socials { display: flex; flex-direction: column; gap: 16px; }
  .socials a { display: inline-flex; width: 24px; height: 24px; transition: opacity .2s; }
  .socials a:hover { opacity: .6; }
  .more-info h2 { text-align: center; color: var(--blue); font-weight: 300; font-size: 40px; line-height: 1.1; margin-bottom: 20px; }
  .contact-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; align-items: center; gap: 12px; font-size: 14px; }
  .contact-list li { display: flex; align-items: center; gap: 10px; }
  .contact-list a { text-decoration: none; }
  .contact-list a:hover { text-decoration: underline; }
  .side-links { justify-self: end; display: flex; flex-direction: column; gap: 28px; font-size: 14px; }
  .side-links a { text-decoration: none; }
  .side-links a:hover { color: var(--blue); text-decoration: underline; }

  /* ---------- Office ---------- */
  .office { padding: 82px 0 80px; }
  .office .container { padding-left: calc(var(--gutter) + 35px); padding-right: calc(var(--gutter) + 35px); }
  .office h2 { color: var(--blue); font-weight: 400; font-size: clamp(40px, 3.9vw, 56px); line-height: 1.1; }
  .office-grid { display: grid; grid-template-columns: minmax(0, 796px) 1fr; gap: 100px; margin-top: 45px; align-items: start; }
  .map { position: relative; display: block; border-radius: 10px; overflow: hidden; box-shadow: 0 0 0 1px #E0E0E0; }
  .map { width: 100%; max-width: 796px; aspect-ratio: 796 / 520; background: #EEF0F2; }
  .map iframe { display: block; width: 100%; height: 100%; }
  .office-details h3 { font-size: 30px; font-weight: 400; line-height: 1.2; }
  .office-details .label { margin-top: 28px; font-size: 11px; letter-spacing: .18em; color: var(--blue); }
  .office-details address, .office-details .contact-block { margin-top: 8px; font-style: normal; font-size: 15px; line-height: 1.7; }
  .office-details .contact-block a { text-decoration: none; }
  .office-details .contact-block a:hover { text-decoration: underline; }
  .directions {
    display: inline-block; margin-top: 36px; font-family: var(--sans); font-size: 11px; letter-spacing: .16em; text-transform: uppercase;
    color: var(--blue); text-decoration: none; border-bottom: 1px solid var(--blue); padding-bottom: 4px;
  }
  .directions:hover { color: var(--navy); border-color: var(--navy); }

  /* ---------- Footer ---------- */
  .site-footer { background: var(--cream); }
  .footer-main { display: grid; grid-template-columns: 1fr repeat(3, 231px); padding-top: 20px; padding-bottom: 20px; }
  .footer-logo { padding: 52px 0 0 11px; }
  .footer-logo img { width: 464px; }
  .footer-col { border-left: 1px solid rgba(36,82,152,.2); padding: 9px 25px 40px; font-size: 14px; line-height: 1.4; }
  .footer-col h2 { font-family: var(--sans); font-weight: 500; font-size: 12px; letter-spacing: .03em; text-transform: uppercase; color: var(--blue); margin-bottom: 26px; }
  .footer-col ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
  .footer-col a:hover { color: var(--blue); }
  .footer-col .contact-mail { font-weight: 700; text-decoration: none; }
  .footer-col p + p { margin-top: 20px; }
  .footer-col .contact-block > * + * { margin-top: 12px; }
  .footer-col .contact-block a { text-decoration: none; }
  .igi { margin-top: 30px; width: 102px; }
  .footer-bottom {
    display: flex; align-items: center; justify-content: space-between; min-height: 65px;
    border-top: 1px solid rgba(36,82,152,.2); font-size: 12px; color: rgba(15,42,75,.6);
  }
  .footer-bottom .socials { flex-direction: row; gap: 12px; }

  /* ---------- Narrow desktop / tablet ---------- */
  /* The 5 menu links need ~515px left of the centred logo. Between 1340 and 1399px a smaller
     menu and logo keep them on one line; below 1340px they would crowd the logo */
  @media (min-width: 1340px) and (max-width: 1399px) {
    .main-nav ul { gap: 14px; }
    .main-nav a { font-size: 13px; }
    .brand img { width: 190px; }
  }
  @media (max-width: 1339px) {
    .main-nav, .header-actions .nav-contact { display: none; }
    .menu-toggle { display: inline-grid; margin-left: -12px; }
  }
  @media (max-width: 1199px) {
    .highlights ul { grid-template-columns: repeat(3, 1fr); row-gap: 40px; }
  }
  @media (max-width: 1100px) {
    .offer-grid { gap: 24px; }
    .offer-card { padding: 40px 32px; }
    .why-grid { gap: 48px; }
    .features { grid-template-columns: 1fr; }
    .office-grid { grid-template-columns: 1fr; gap: 40px; }
    .footer-main { grid-template-columns: repeat(3, 1fr); }
    .footer-logo { grid-column: 1 / -1; padding: 30px 0; }
    .footer-col:nth-child(2) { border-left: 0; padding-left: 0; }
    .philosophy .philosophy-logo { margin-left: 0; }
    .philosophy-text { margin-right: 0; }
  }
  @media (max-width: 900px) {
    .offer-grid { grid-template-columns: 1fr; max-width: 560px; }
    .offer-card { min-height: 0; }
    .why-grid { grid-template-columns: 1fr; }
    .why-media { position: static; }
    .why-media img { aspect-ratio: 4 / 3; }
    .features { grid-template-columns: 1fr 1fr; }
  }

  /* ---------- Mobile ---------- */
  @media (max-width: 767px) {
    :root { --gutter: 20px; --header-h: 64px; }
    .header-actions .header-link, .header-user { display: none; }
    .header-login { margin-left: 0; padding: 8px 12px; font-size: 11px; letter-spacing: .08em; }
    .header-actions { margin-right: -12px; }
    .brand img { width: 150px; }

    /* Portrait banner: the ring sits in the upper third, so keep that area clear and put the text below it */
    .hero { min-height: 150vw; }
    .hero > picture img { object-position: 85% top; }
    .hero::after {
      background: linear-gradient(to bottom, rgba(15,42,75,0) 0%, rgba(15,42,75,0) 30%, rgba(15,42,75,.55) 50%, rgba(15,42,75,.9) 100%);
    }
    .hero-content { padding-top: 74vw; padding-bottom: 40px; }
    .hero h1 { font-size: 36px; margin-top: 18px; }
    .hero .tagline { font-size: 18px; }
    .hero p.lead { font-size: 16px; }
    .hero-actions { flex-direction: column; }
    .hero-actions .btn { width: 100%; }

    .intro { padding: 64px 0; }
    .intro .container { flex-direction: column; gap: 36px; }
    .intro h2 { font-size: 28px; }
    .intro .intro-logo { width: 240px; margin: 0; }

    .highlights { padding: 48px 0; }
    .highlights ul { grid-template-columns: 1fr; gap: 28px; padding: 0; }

    .portfolio { padding: 64px 0 48px; }
    .section-head .lead { margin-top: 18px; }
    .offer-grid { gap: 16px; margin-top: 40px; }
    .offer-card { padding: 32px 28px; }

    .gallery { padding-bottom: 24px; }
    .gallery-track img { width: 82vw; }
    .gallery-nav { display: none; }

    .why { padding: 64px 0; }
    .why-grid { gap: 36px; }
    .features { grid-template-columns: 1fr; gap: 24px; margin-top: 36px; }
    .why-intro p { font-size: 16px; }

    .philosophy { padding: 64px 0; }
    .philosophy .container { flex-direction: column-reverse; gap: 48px; }
    .philosophy .philosophy-logo { width: 260px; }
    .philosophy-text { text-align: center; }

    .process { padding: 0 0 72px; }
    .timeline { margin-top: 40px; padding-left: 36px; }
    .step { margin-bottom: 28px; }
    .step::before { left: -36px; }
    .step-card { padding: 20px; }

    .cta { padding: 72px 0; }
    .cta .btn { width: 100%; max-width: 320px; }

    .more-info { padding: 56px 0; }
    .more-info .container { grid-template-columns: 1fr; gap: 28px; justify-items: center; }
    .more-info .socials { flex-direction: row; order: -1; }
    .more-info h2 { font-size: 34px; }
    .contact-list { text-align: center; }
    .side-links { justify-self: center; flex-direction: row; gap: 32px; }

    .office { padding: 56px 0; }
    .office .container { padding-left: var(--gutter); padding-right: var(--gutter); }
    .office-grid { margin-top: 28px; }
    .map { width: 100%; max-width: 100%; aspect-ratio: 316 / 200; }

    .footer-main { grid-template-columns: 1fr; padding-top: 0; }
    .footer-logo { padding: 36px 0 24px; }
    .footer-logo img { width: 240px; }
    .footer-col, .footer-col:nth-child(2) { border-left: 0; border-top: 1px solid rgba(36,82,152,.2); padding: 24px 0; }
    .footer-col h2 { margin-bottom: 16px; }
    .footer-bottom { min-height: 56px; }
  }

  /* ---------- Offer popup ---------- */
  .offer-popup {
    position: fixed; inset: 0; z-index: 70; display: grid; place-items: center; padding: 24px;
    visibility: hidden; opacity: 0; pointer-events: none; transition: opacity .35s ease, visibility 0s linear .35s;
  }
  .offer-popup.open { visibility: visible; opacity: 1; pointer-events: auto; transition: opacity .35s ease; }
  .offer-popup-backdrop { position: absolute; inset: 0; background: rgba(15,42,75,.55); }
  .offer-popup-box {
    position: relative; display: grid; grid-template-columns: 46% 1fr; width: min(800px, 100%);
    background: var(--white); box-shadow: 0 24px 60px rgba(15,42,75,.3);
    transform: translateY(16px); transition: transform .35s ease;
  }
  .offer-popup.open .offer-popup-box { transform: none; }
  .offer-popup-media img { width: 100%; height: 100%; object-fit: cover; }
  .offer-popup-body { padding: 56px 40px 40px; display: flex; flex-direction: column; justify-content: center; }
  .offer-popup-body .kicker { margin-bottom: 12px; }
  .offer-popup-body h2 { font-weight: 400; font-size: 32px; line-height: 1.15; color: var(--blue); }
  .offer-popup-body .lead { margin-top: 14px; font-size: 17px; line-height: 1.5; }
  .offer-popup-body .btn { margin-top: 26px; width: 100%; }
  .offer-popup-body .note { margin-top: 16px; font-size: 14px; line-height: 1.5; opacity: .75; }
  .offer-popup-close {
    position: absolute; top: 12px; right: 12px; width: 44px; height: 44px; display: grid; place-items: center;
    background: none; border: 0; border-radius: 50%; cursor: pointer;
  }
  .offer-popup-close:hover { background: var(--cream); }
  .offer-popup-close svg { width: 30px; height: 30px; }
  @media (max-width: 767px) {
    .offer-popup { padding: 16px; }
    .offer-popup-box { grid-template-columns: 1fr; max-height: calc(100vh - 32px); overflow-y: auto; }
    .offer-popup-media img { height: 190px; object-position: center 60%; }
    .offer-popup-body { padding: 28px 24px 28px; }
    .offer-popup-body h2 { font-size: 26px; }
    .offer-popup-body .lead { font-size: 16px; }
    .offer-popup-close { background: rgba(255,255,255,.9); top: 8px; right: 8px; }
  }

  @media (prefers-reduced-motion: reduce) {
    html, .gallery-track { scroll-behavior: auto; }
    *, *::before, *::after { transition: none !important; }
  }
</style>
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <div>
      <button class="icon-btn menu-toggle" aria-label="Open menu" aria-controls="drawer" aria-expanded="false">
        <img src="assets/logo-header-drawer-mobile.svg" alt="" width="14" height="20">
      </button>
      <nav class="main-nav" aria-label="Main">
        <ul>
          <li><a href="#top" aria-current="page">Home</a></li>
          <li><a href="#certified-diamonds">Certified Diamonds</a></li>
          <li><a href="#calibrated-melees">Calibrated Melees</a></li>
          <li><a href="<?= $h($linkJewellery) ?>">Diamond Jewellery</a></li>
          <li><a href="#about">About Us</a></li>
        </ul>
      </nav>
    </div>
    <a class="brand" href="#top" aria-label="Orion Snow — home">
      <img src="assets/logo-main.png" alt="Orion Snow" width="464" height="88">
    </a>
    <div class="header-actions">
      <a class="header-link nav-contact" href="#contact">Contact</a>
      <a class="header-link" href="<?= $h($linkInventory) ?>">Inventory</a>
      <a class="icon-btn" href="<?= $h(main_app_url('/modules/user/view_results.php')) ?>" aria-label="Selected diamonds">
        <img src="assets/logo-cart.svg" alt="" width="22" height="22">
      </a>
      <?php if ($user !== null): ?>
        <span class="header-user">Signed in as <?= $h((string)($user['username'] ?? '')) ?></span>
        <a class="header-login is-signed-in" href="<?= $h($linkLogout) ?>">Log out</a>
      <?php else: ?>
        <a class="header-login" href="<?= $h($linkLogin) ?>">Login</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<div class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer-backdrop" data-close></div>
  <div class="drawer-panel" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="drawer-head">
      <img src="assets/logo-main.png" alt="Orion Snow" width="464" height="88">
      <button class="icon-btn" data-close aria-label="Close menu">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#0F2A4B" stroke-width="1.5" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg>
      </button>
    </div>
    <nav aria-label="Mobile">
      <ul>
        <li><a href="#top" aria-current="page">Home</a></li>
        <li><a href="#certified-diamonds">Certified Diamonds</a></li>
        <li><a href="#calibrated-melees">Calibrated Melees</a></li>
        <li><a href="<?= $h($linkJewellery) ?>">Diamond Jewellery</a></li>
        <li><a href="#about">About Us</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="<?= $h($linkInventory) ?>">Inventory</a></li>
        <?php if ($user !== null): ?>
          <li><a href="<?= $h($linkLogout) ?>">Log out (<?= $h((string)($user['username'] ?? '')) ?>)</a></li>
        <?php else: ?>
          <li><a href="<?= $h($linkLogin) ?>">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</div>

<main id="top">

  <!-- 1. Hero -->
  <section class="hero">
    <picture>
      <source media="(max-width: 767px)" srcset="assets/banner-main-mobile.jpg" width="1024" height="1536">
      <img src="assets/banner-main.jpg" alt="Oval lab-grown diamond ring with pear side stones on blue ice crystals" width="1667" height="943" fetchpriority="high">
    </picture>
    <div class="container hero-content">
      <span class="hero-badge">B2B Diamond Partner</span>
      <h1>Your Trusted Partner for Lab-Grown Diamonds</h1>
      <p class="tagline">Quality. Precision. Reliability.</p>
      <p class="lead">Discover a complete range of certified lab-grown diamonds, precisely calibrated melees in all shapes and sizes, and beautifully crafted lab-grown diamond jewellery.</p>
      <div class="hero-actions">
        <a class="btn btn-light" href="<?= $h($linkInventory) ?>">Explore Our Inventory</a>
        <a class="btn btn-outline" href="#get-in-touch">Become a Partner</a>
      </div>
    </div>
  </section>

  <!-- Intro -->
  <section class="intro">
    <div class="container">
      <h2><span>Built on trust,</span> Orion Snow combines exceptional product quality with dependable service and fast, reliable delivery.</h2>
      <img class="intro-logo" src="assets/logo-main.png" alt="" width="464" height="88" loading="lazy">
    </div>
  </section>

  <!-- Key benefits -->
  <section class="highlights" aria-label="Why work with Orion Snow">
    <div class="container">
      <ul>
        <li><strong>Fast Delivery</strong>Quick, reliable delivery when you need it</li>
        <li><strong>No Minimum Order</strong>Orders of any size — no minimum order</li>
        <li><strong>Certified</strong>Certified lab-grown diamonds</li>
        <li><strong>Calibrated Melees</strong>Precision-calibrated melees</li>
        <li><strong>Jewellery</strong>Ready-to-sell and custom jewellery solutions</li>
      </ul>
    </div>
  </section>

  <!-- 2. Product portfolio -->
  <section class="portfolio" id="portfolio">
    <div class="container">
      <div class="section-head">
        <span class="kicker">Our Product Portfolio</span>
        <h2 class="section-title">Everything You Need. One Trusted Partner.</h2>
        <p class="lead">From individual certified stones to precision-calibrated melees and finished jewellery, Orion Snow offers a comprehensive product range to support your business.</p>
      </div>
      <div class="offer-grid">
        <article class="offer-card" id="certified-diamonds">
          <span class="num">01</span>
          <h3>Certified Lab-Grown Diamonds</h3>
          <p>A wide selection of certified white and fancy colour lab-grown diamonds in various sizes, shapes and specifications.</p>
        </article>
        <article class="offer-card" id="calibrated-melees">
          <span class="num">02</span>
          <h3>Precision-Calibrated Melees</h3>
          <p>Precisely matched melees in all shapes, sizes and colours. Consistency and accuracy for every jewellery creation.</p>
        </article>
        <article class="offer-card" id="diamond-jewellery">
          <span class="num">03</span>
          <h3>Lab-Grown Diamond Jewellery</h3>
          <p>A versatile collection of fine diamond jewellery, from everyday essentials to sophisticated statement pieces.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- Gallery -->
  <section class="gallery" aria-label="Product gallery">
    <div class="gallery-track" tabindex="0" role="region" aria-label="Product images, scroll sideways for more">
      <img src="assets/product01.jpg" alt="Pear-cut lab-grown diamond ring with side stones" width="425" height="533" loading="lazy">
      <img src="assets/product02.jpg" alt="Oval lab-grown diamond ring with pear side stones" width="425" height="533" loading="lazy">
      <img src="assets/product03.jpg" alt="Pear-cut lab-grown diamond ring with baguettes" width="425" height="533" loading="lazy">
      <img src="assets/product-04.jpg" alt="Emerald-cut lab-grown diamond solitaire ring" width="425" height="533" loading="lazy">
    </div>
    <button class="gallery-nav prev" aria-label="Previous images" disabled>
      <svg viewBox="0 0 20 20" fill="none" stroke="#0F2A4B" stroke-width="1.5"><path d="M12.5 4.5L7 10l5.5 5.5"/></svg>
    </button>
    <button class="gallery-nav next" aria-label="Next images">
      <svg viewBox="0 0 20 20" fill="none" stroke="#0F2A4B" stroke-width="1.5"><path d="M7.5 4.5L13 10l-5.5 5.5"/></svg>
    </button>
  </section>

  <!-- 3. Why Orion Snow -->
  <section class="why" id="about">
    <div class="container why-grid">
      <div class="why-media">
        <img src="assets/product-04.jpg" alt="Emerald-cut lab-grown diamond solitaire ring" width="425" height="533" loading="lazy">
      </div>
      <div>
        <div class="why-intro">
          <span class="kicker">Why Orion Snow?</span>
          <h2 class="section-title">A Partner You Can Rely On.</h2>
          <p>In the fast-moving diamond and jewellery industry, having the right supply partner makes all the difference.</p>
          <p>At Orion Snow, we understand that your business needs more than just quality diamonds. It needs consistency, flexibility, speed and trust.</p>
        </div>
        <div class="features">
          <div class="feature">
            <h3>Personal, Responsive Service</h3>
            <p>A dedicated partner offering easy access, clear communication and responsive service to businesses of every size.</p>
          </div>
          <div class="feature">
            <h3>Fast &amp; Reliable Delivery</h3>
            <p>Quick, dependable shipping that helps you meet customer demands without unnecessary delays.</p>
          </div>
          <div class="feature">
            <h3>No Minimum Order</h3>
            <p>Whether you need a single stone or a substantial parcel, we accommodate orders of every size.</p>
          </div>
          <div class="feature">
            <h3>Precision &amp; Consistency</h3>
            <p>Carefully calibrated melees and quality-controlled diamonds to meet the exact requirements of your jewellery creations.</p>
          </div>
          <div class="feature">
            <h3>A Complete Product Range</h3>
            <p>From certified loose diamonds to melees and finished jewellery, everything under one roof.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. Our philosophy -->
  <section class="philosophy">
    <div class="container">
      <img class="philosophy-logo" src="assets/logo-main.png" alt="" width="464" height="88" loading="lazy">
      <div class="philosophy-text">
        <span class="kicker">Our Philosophy</span>
        <h2>Big or Small. Every Order Matters.</h2>
        <p>At Orion Snow, we believe that strong partnerships are built on trust, flexibility and consistent service.</p>
        <p>We understand that every business has different requirements. That's why we don't believe in minimum order quantities or one-size-fits-all solutions.</p>
        <p>Whether you are an independent jeweller, a jewellery manufacturer, a wholesaler or an established jewellery brand, we are here to support your business with the right products at the right time.</p>
      </div>
    </div>
  </section>

  <!-- 5. How it works -->
  <section class="process">
    <div class="container">
      <div class="section-head">
        <span class="kicker">How It Works</span>
        <h2 class="section-title">A Simple, Dependable Way to Do Business</h2>
      </div>
      <div class="timeline">
        <span class="timeline-progress" aria-hidden="true"></span>
        <ol class="steps">
          <li class="step active">
            <div class="step-card">
              <div class="step-label">STEP 01</div>
              <h3>Browse our collection</h3>
              <p>Explore our inventory of certified diamonds, melees and jewellery.</p>
            </div>
          </li>
          <li class="step">
            <div class="step-card">
              <div class="step-label">STEP 02</div>
              <h3>Choose what you need</h3>
              <p>Select individual stones, parcels or jewellery in the quantities that suit your business.</p>
            </div>
          </li>
          <li class="step">
            <div class="step-card">
              <div class="step-label">STEP 03</div>
              <h3>We deliver</h3>
              <p>Enjoy reliable service and fast, secure delivery straight to your business.</p>
            </div>
          </li>
        </ol>
      </div>
    </div>
  </section>

  <!-- 6. Final call to action -->
  <section class="cta" id="get-in-touch">
    <div class="container">
      <h2>Let's Grow Together.</h2>
      <p class="tagline">Your business. Our diamonds. One reliable partnership.</p>
      <p class="body">Looking for a dependable supplier of lab-grown diamonds, calibrated melees or fine diamond jewellery? Let's discuss how Orion Snow can support your business.</p>
      <a class="btn btn-light" href="mailto:info@orionsnow.com?subject=Partnership%20enquiry">Get in Touch</a>
    </div>
  </section>

  <!-- More information -->
  <section class="more-info" id="contact">
    <div class="container">
      <div class="socials">
        <a href="#" aria-label="Facebook"><img src="assets/logo-facebook.svg" alt="" width="24" height="24"></a>
        <a href="#" aria-label="Instagram"><img src="assets/logo-instagram.svg" alt="" width="24" height="24"></a>
      </div>
      <div>
        <h2>More Information</h2>
        <ul class="contact-list">
          <li><img src="assets/logo-email.svg" alt="" width="20" height="20"><a href="mailto:info@orionsnow.com">info@orionsnow.com</a></li>
          <li><img src="assets/logo-phone.svg" alt="" width="20" height="20"><a href="tel:+32487508727">+32 487 50 87 27</a></li>
          <li><img src="assets/logo-location.svg" alt="" width="20" height="20"><span>Hoveniersstraat 9, 2018 Antwerp, Belgium</span></li>
        </ul>
      </div>
      <div class="side-links">
        <a href="#office">Contact</a>
        <a href="#about">About Us</a>
      </div>
    </div>
  </section>

  <!-- Office -->
  <section class="office" id="office">
    <div class="container">
      <h2>Our Office</h2>
      <div class="office-grid">
        <div class="map">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2499.1804375370957!2d4.416163676613975!3d51.21575163196716!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c3f7023085caad%3A0x6f8b511e41b4ec7c!2sHoveniersstraat%209%2C%202018%20Antwerpen%2C%20Belgium!5e0!3m2!1sen!2sin!4v1793707667874!5m2!1sen!2sin"
            width="796" height="520" title="Map: Orion Snow office, Hoveniersstraat 9, 2018 Antwerp"
            style="border:0;" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
        <div class="office-details">
          <h3>Orion Snow</h3>
          <div class="label eyebrow">Address</div>
          <address>Hoveniersstraat 9<br>2018 Antwerp<br>Belgium</address>
          <div class="label eyebrow">Contact</div>
          <div class="contact-block">
            <a href="tel:+32487508727">+32 487 50 87 27</a><br>
            <a href="mailto:info@orionsnow.com">info@orionsnow.com</a>
          </div>
          <a class="directions" href="https://www.google.com/maps/dir/?api=1&destination=Hoveniersstraat+9+2018+Antwerpen" target="_blank" rel="noopener">Get directions</a>
        </div>
      </div>
    </div>
  </section>

</main>

<footer class="site-footer">
  <div class="container footer-main">
    <div class="footer-logo">
      <img src="assets/logo-main.png" alt="Orion Snow" width="464" height="88" loading="lazy">
    </div>
    <div class="footer-col">
      <h2>Useful Links</h2>
      <ul>
        <li><a href="#certified-diamonds">Certified Diamonds</a></li>
        <li><a href="#calibrated-melees">Calibrated Melees</a></li>
        <li><a href="<?= $h($linkJewellery) ?>">Diamond Jewellery</a></li>
        <li><a href="#about">About Us</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="<?= $h($linkInventory) ?>">Inventory</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h2>Contact</h2>
      <div class="contact-block">
        <div><a class="contact-mail" href="mailto:info@orionsnow.com">info@orionsnow.com</a></div>
        <div><a href="tel:+32487508727">+32 487 50 87 27</a></div>
        <div>Hoveniersstraat 9, 2018 Antwerp, Belgium</div>
      </div>
    </div>
    <div class="footer-col">
      <h2>Orion Snow</h2>
      <p>Your Trusted Partner for<br>Lab-Grown Diamonds.</p>
      <p>Certified lab-grown diamonds, precision-calibrated melees and diamond jewellery for professional jewellers — with fast, reliable delivery and no minimum order.</p>
      <img class="igi" src="assets/logo-igi.png" alt="IGI certified" width="140" height="58" loading="lazy">
    </div>
  </div>
  <div class="container">
    <div class="footer-bottom">
      <span>© <span class="year">2026</span> <a href="#top">Orion Snow</a></span>
      <div class="socials">
        <a href="#" aria-label="Facebook"><img src="assets/logo-facebook.svg" alt="" width="24" height="24"></a>
        <a href="#" aria-label="Instagram"><img src="assets/logo-instagram.svg" alt="" width="24" height="24"></a>
      </div>
    </div>
  </div>
</footer>

<!-- Offer popup: opens 2 seconds after every page load -->
<div class="offer-popup" id="offer-popup" aria-hidden="true">
  <div class="offer-popup-backdrop" data-popup-close></div>
  <div class="offer-popup-box" role="dialog" aria-modal="true" aria-labelledby="offer-popup-title">
    <div class="offer-popup-media">
      <img src="assets/product03.jpg" alt="Pear-cut lab-grown diamond ring with baguettes" width="425" height="533">
    </div>
    <div class="offer-popup-body">
      <span class="kicker">New Partner Offer</span>
      <h2 id="offer-popup-title">Free Insured Shipping on Your First Order</h2>
      <p class="lead">Open a trade account with Orion Snow and your first order ships free and fully insured, whether it's a single stone or a full parcel.</p>
      <a class="btn" href="#get-in-touch" data-popup-close>Claim the Offer</a>
      <p class="note">For jewellers, manufacturers, wholesalers and jewellery brands. No minimum order.</p>
    </div>
    <button class="offer-popup-close" data-popup-close aria-label="Close offer">
      <svg viewBox="0 0 30 30" fill="none" stroke="#0F2A4B" stroke-width="1.5" stroke-linecap="round"><circle cx="15" cy="15" r="13"/><path d="M10.5 10.5l9 9M19.5 10.5l-9 9"/></svg>
    </button>
  </div>
</div>

<script>
  // Offer popup: shows 2s after load on every visit (nothing is remembered, so it returns on reload)
  (function () {
    const popup = document.getElementById('offer-popup');
    const closeBtn = popup.querySelector('.offer-popup-close');
    let lastFocus = null;
    const setOpen = (open) => {
      popup.classList.toggle('open', open);
      popup.setAttribute('aria-hidden', String(!open));
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) { lastFocus = document.activeElement; closeBtn.focus({ preventScroll: true }); }
      else if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
    };
    popup.querySelectorAll('[data-popup-close]').forEach(el => el.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', e => {
      if (!popup.classList.contains('open')) return;
      if (e.key === 'Escape') { setOpen(false); return; }
      if (e.key === 'Tab') {
        const items = [...popup.querySelectorAll('a[href], button')];
        const first = items[0], last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    window.addEventListener('load', () => setTimeout(() => setOpen(true), 2000));
  })();

  // Footer year
  document.querySelector('.year').textContent = new Date().getFullYear();

  // Mobile drawer
  (function () {
    const drawer = document.getElementById('drawer');
    const toggle = document.querySelector('.menu-toggle');
    const setOpen = (open) => {
      drawer.classList.toggle('open', open);
      drawer.setAttribute('aria-hidden', String(!open));
      toggle.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) drawer.querySelector('.icon-btn[data-close]').focus(); else toggle.focus();
    };
    toggle.addEventListener('click', () => setOpen(true));
    drawer.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', () => setOpen(false)));
    drawer.querySelectorAll('nav a').forEach(a => a.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', e => {
      if (!drawer.classList.contains('open')) return;
      if (e.key === 'Escape') { setOpen(false); return; }
      // Keep Tab focus inside the open menu
      if (e.key === 'Tab') {
        const items = [...drawer.querySelectorAll('a[href], button')];
        const first = items[0], last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
  })();

  // Process timeline: highlight steps as they reach the lower part of the screen
  (function () {
    const steps = [...document.querySelectorAll('.step')];
    const timeline = document.querySelector('.timeline');
    const progress = document.querySelector('.timeline-progress');
    const update = () => {
      const mid = window.innerHeight * 0.6;
      let last = 0;
      steps.forEach((s, i) => {
        const on = i === 0 || s.getBoundingClientRect().top < mid;
        s.classList.toggle('active', on);
        if (on) last = i;
      });
      const t = timeline.getBoundingClientRect().top;
      const dot = steps[last].getBoundingClientRect().top + 16;
      progress.style.height = Math.max(0, dot - t) + 'px';
    };
    // Run at most once per frame while scrolling
    let queued = false;
    const schedule = () => {
      if (queued) return;
      queued = true;
      requestAnimationFrame(() => { queued = false; update(); });
    };
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule);
    update();
  })();

  // Gallery arrows
  (function () {
    const track = document.querySelector('.gallery-track');
    const prev = document.querySelector('.gallery-nav.prev');
    const next = document.querySelector('.gallery-nav.next');
    const step = () => track.querySelector('img').getBoundingClientRect().width + 13;
    const sync = () => {
      prev.disabled = track.scrollLeft < 5;
      next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;
    };
    prev.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    track.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync);
    sync();
  })();
</script>
</body>
</html>
