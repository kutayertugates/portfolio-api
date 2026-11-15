-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 15 Kas 2026, 16:13:17
-- Sunucu sürümü: 10.11.16-MariaDB
-- PHP Sürümü: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `kutayertugcom`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blogpost`
--

CREATE TABLE `blogpost` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('published','draft','archived') NOT NULL DEFAULT 'draft',
  `author_id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL,
  `cover_image` varchar(512) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `description` text NOT NULL,
  `seo_description` varchar(170) DEFAULT NULL,
  `content` longtext NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `blogpost`
--

INSERT INTO `blogpost` (`id`, `status`, `author_id`, `slug`, `cover_image`, `title`, `seo_title`, `description`, `seo_description`, `content`, `published_at`, `updated_at`, `created_at`) VALUES
(1, 'published', 1, 'test-blog-1', NULL, 'test blog', 'test blog', 'this is a test blog', 'this is a test blog', '\n<h1>Yazılım Geliştirmenin Görünmeyen Yüzü</h1>\n\n<p>\n  Yazılım geliştirmek yalnızca kod yazmaktan ibaret değildir.\n  Asıl mesele, bir problemi anlamak ve onu mümkün olan en\n  basit şekilde çözebilmektir.\n</p>\n\n<h2>1. Basitlik Neden Önemlidir?</h2>\n\n<p>\n  İyi bir yazılımın en önemli özelliklerinden biri\n  sürdürülebilir olmasıdır. Gereksiz karmaşıklık,\n  zaman içerisinde geliştirme sürecini zorlaştırır.\n</p>\n\n<blockquote>\n  \"Her şeyi mümkün olduğunca basit yapın, ama daha basit değil.\"\n</blockquote>\n\n<h2>2. Birkaç Temel Prensip</h2>\n\n<ul>\n  <li>Gereksiz soyutlamalardan kaçının.</li>\n  <li>İhtiyaç olmayan özellikleri geliştirmeyin.</li>\n  <li>Kodun okunabilirliğine önem verin.</li>\n  <li>Tekrar eden kodları uygun şekilde düzenleyin.</li>\n</ul>\n\n<h2>3. Örnek Kod</h2>\n\n<p>\n  Örneğin JavaScript kullanarak basit bir fonksiyon\n  oluşturabiliriz:\n</p>\n\n<pre><code>function greet(name) {\n    return `Merhaba, ${name}!`;\n}\n\nconsole.log(greet(\"Kutay\"));</code></pre>\n\n<h2>4. Sonuç</h2>\n\n<p>\n  Yazılım geliştirme sürecinde önemli olan çok fazla kod\n  yazmak değil, doğru problemi doğru şekilde çözmektir.\n</p>\n\n<hr>\n\n<p>üstte çizgi var</p>', '2026-11-13 12:24:02', NULL, '2026-11-12 12:22:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fullname` varchar(200) NOT NULL,
  `email` varchar(254) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `contacts`
--

INSERT INTO `contacts` (`id`, `fullname`, `email`, `subject`, `message`, `sent_at`) VALUES
(1, 'test name', 'test@test.com', 'test subject', 'test message', '2026-11-12 14:40:59'),
(2, 'test2323', 'kutayertug@gmail.co', 'TC Zaten Kayıtlı Hatası', 'asdasdasdsad', '2026-11-12 16:37:00'),
(3, 'sadfadsf', 'asdfasf@asdfaf.com', 'adsfasdf', 'asdfasdf', '2026-11-13 11:34:11');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `username` varchar(32) NOT NULL,
  `email` varchar(254) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_staff` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `join_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password`, `is_staff`, `is_active`, `join_at`) VALUES
(1, 'test name', 'test lastname', 'testuser', 'test@test.com', '$argon2id$v=19$m=65536,t=4,p=1$VDROekVyVmlkc2ZNNzJuUw$Jn/fJWFhUecFiTrb1F5jPSkZ94bXsN0YZ+zgN66rZbk', 0, 1, '2026-11-11 13:18:41');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `blogpost`
--
ALTER TABLE `blogpost`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`),
  ADD KEY `author_id` (`author_id`);

--
-- Tablo için indeksler `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usernme` (`username`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `blogpost`
--
ALTER TABLE `blogpost`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `blogpost`
--
ALTER TABLE `blogpost`
  ADD CONSTRAINT `blogpost_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
