-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.infinityfree.com
-- Generation Time: Jun 16, 2026 at 11:55 PM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41750337_chillcom`
--

-- --------------------------------------------------------

--
-- Table structure for table `deleted_accounts`
--

CREATE TABLE `deleted_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by_ip` varchar(45) DEFAULT NULL,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_accounts`
--

INSERT INTO `deleted_accounts` (`id`, `user_id`, `username`, `email`, `deleted_at`, `deleted_by_ip`, `reason`) VALUES
(1, 5, 'cobacoba', 'hapus@testing.com', '2025-12-24 13:40:28', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `max_participants` int(11) NOT NULL,
  `participants` int(11) DEFAULT 0,
  `prize` varchar(255) DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `created_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_type`, `event_date`, `location`, `max_participants`, `participants`, `prize`, `rules`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Minecraft Build Battle', 'Creative building competition with themes', 'Build Contest', '2024-12-15 19:00:00', 'build.chillcom.net', 50, 24, '$100 + Diamond Rank', 'No mods, 2 hours time limit', 'admin', '2025-12-19 14:33:07', '2025-12-19 14:33:07'),
(2, 'PvP Tournament', 'Weekly PvP tournament with prizes', 'PvP Competition', '2024-12-10 20:00:00', 'pvp.chillcom.net', 32, 32, 'Diamond Sword + $50', 'No potions, Iron gear only', 'admin', '2025-12-19 14:33:07', '2025-12-19 14:33:07'),
(3, 'Community Survival', 'Survival event with special challenges', 'Community Event', '2024-12-20 18:00:00', 'survival.chillcom.net', 100, 67, 'Special Titles', 'Hard difficulty, Teams of 4', 'admin', '2025-12-19 14:33:07', '2025-12-19 14:33:07'),
(4, 'Parkour Challenge', 'Extreme parkour competition', 'Tournament', '2024-12-05 17:00:00', 'parkour.chillcom.net', 40, 18, 'Gold Blocks + $75', 'No flying, No creative mode', 'admin', '2025-12-19 14:33:07', '2025-12-19 14:33:07'),
(5, 'Holiday Special Event', 'Christmas themed building event', 'Special Event', '2024-12-25 02:00:00', 'holiday.chillcom.net', 60, 45, 'Santa Hat Cosmetic', 'Christmas theme required', 'admin', '2025-12-19 14:33:07', '2025-12-19 14:34:01'),
(9, 'Opening Server MC', 'HALO PENDUDUK ACHILL KAMI INGIN MENGINFORMASIKAN KALAU SERVER MINECRAFT ACHILL SUDAH READY DAN KALIAN BISA MEMAINKANNYA SEKARANG JUGA, IP :\r\n\r\nUntuk Java\r\nIP : nl-2.nura.host:25586\r\nVersi: 1.21.4+\r\nServer ResourcePacks: Enabled\r\n\r\nUntuk Bedrock\r\nBedrock: nl-2.nura.host\r\nPort: 25586', 'Community Event', '2025-12-20 13:25:00', 'nl-2.nura.host:25586', 50, 8, '', '', 'admin', '2025-12-20 13:26:22', '2026-06-10 14:18:07'),
(10, 'testingbaru', 'nyoba testing', 'Special Event', '2025-12-24 20:47:00', 'nl-2.nura.host:25586', 50, 2, '0', 'takde laa', '', '2025-12-24 13:48:10', '2025-12-24 13:56:34'),
(11, 'sidang s4', 'testing pengujian sistem s4', 'Community Event', '2026-06-10 16:21:00', 'play.chillelevent.net', 50, 1, '0', 'tidak!! sidang lagi', '', '2026-06-10 09:22:01', '2026-06-10 14:18:51'),
(12, 'th2', 'anjim', 'Community Event', '2026-06-19 21:07:00', 'play.chillelevent.net', 10, 1, '0', '', '', '2026-06-10 14:07:27', '2026-06-10 14:18:54');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`id`, `user_id`, `event_id`, `registration_date`, `status`) VALUES
(3, 4, 10, '2025-12-24 13:50:41', 'registered'),
(4, 3, 10, '2025-12-24 13:56:34', 'registered'),
(5, 3, 11, '2026-06-10 14:18:51', 'registered'),
(6, 3, 12, '2026-06-10 14:18:54', 'registered');

-- --------------------------------------------------------

--
-- Table structure for table `export_logs`
--

CREATE TABLE `export_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `export_type` varchar(20) DEFAULT NULL,
  `export_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(500) NOT NULL,
  `link` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'TG',
  `is_popular` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `name`, `image`, `link`, `category`, `is_popular`, `created_at`, `updated_at`) VALUES
(1, 'Mobile Legends', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcToU-XuJ3JrmdL8gP14iTnhc9srMDkQIU8zVQ&s', 'mobile_legends.php', 'TG', 1, '2025-12-24 21:03:56', '2025-12-24 21:03:56'),
(2, 'Roblox', 'https://images.rbxcdn.com/5348266ea6c5e67b19d6a814cbbb70f6.jpg', 'roblox.php', 'TG', 1, '2025-12-24 21:03:56', '2025-12-24 21:03:56'),
(3, 'Free Fire', 'https://play-lh.googleusercontent.com/J9qj3g7Qmh3oe1s1nA3bbXdJPhL7QCMKJfILU-47itkXSgTbPwbn6QEn6hSAr-yhT0c', 'free_fire.php', 'TG', 0, '2025-12-24 21:03:56', '2025-12-24 21:03:56'),
(4, 'Valorant', 'https://images.contentstack.io/v3/assets/bltb6530b271fddd0b1/blt158572ec37653cf3/5e7cd235e8e6f60c950d2aaa/V_AGENTS_587x900_Jett.png', 'valorant.php', 'TG', 1, '2025-12-24 21:03:56', '2025-12-24 21:03:56'),
(5, 'Steam Wallet', 'https://cdn.akamai.steamstatic.com/store/home/store_home_share.jpg', 'steam.php', 'TG', 0, '2025-12-24 21:03:56', '2025-12-24 21:03:56');

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(8, 'azzarthfatly@gmail.com', '984ee6f07c3678468d5807d8b328ed4b90cb095a7273d8ad7851f0b524b68cb5', '2025-12-20 17:35:27', '2025-12-20 15:35:27');

-- --------------------------------------------------------

--
-- Table structure for table `ranks`
--

CREATE TABLE `ranks` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `color` varchar(20) DEFAULT '#7289da',
  `badge_type` varchar(20) DEFAULT 'custom',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ranks`
--

INSERT INTO `ranks` (`id`, `name`, `price`, `color`, `badge_type`, `created_at`) VALUES
(1, 'Legend', '100.00', '#ffd700', 'legend', '2025-12-24 21:03:56'),
(2, 'Hero', '50.00', '#c0c0c0', 'hero', '2025-12-24 21:03:56'),
(3, 'Ultra', '15.00', '#cd7f32', 'ultra', '2025-12-24 21:03:56');

-- --------------------------------------------------------

--
-- Table structure for table `rank_features`
--

CREATE TABLE `rank_features` (
  `id` int(11) NOT NULL,
  `rank_id` int(11) NOT NULL,
  `feature_text` text NOT NULL,
  `is_premium` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rank_features`
--

INSERT INTO `rank_features` (`id`, `rank_id`, `feature_text`, `is_premium`) VALUES
(1, 1, 'Fly', 0),
(2, 1, 'Change Name', 0),
(3, 1, 'Topi', 0),
(4, 1, 'Feed', 0),
(5, 1, 'Heal', 0),
(6, 1, 'Anvil', 0),
(7, 1, 'Craft', 0),
(8, 1, 'Ptime PWeather', 0),
(9, 1, 'Free Pet Request Bintang 5', 1),
(10, 1, 'Enderchest', 0),
(11, 1, 'GrindStone', 0),
(12, 1, 'Free Claim 200.000', 0),
(13, 1, 'SetHome Max 15', 0),
(14, 2, 'Ptime PWeather', 0),
(15, 2, 'Free Pet Request Bintang 4', 1),
(16, 2, 'Anvil', 0),
(17, 2, 'Craft', 0),
(18, 2, 'Topi', 0),
(19, 2, 'Free Claim 50.000', 0),
(20, 2, 'Set Home 10', 0),
(21, 3, 'Free Pet Request Bintang 3', 1),
(22, 3, 'Topi', 0),
(23, 3, 'Free Claim 10.000', 0),
(24, 3, 'Sethome 5', 0);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'CHILLCOM', 'Website name', '2025-12-24 15:38:04', '2025-12-24 15:38:04'),
(2, 'maintenance_mode', '0', 'Enable maintenance mode', '2025-12-24 15:38:04', '2026-06-10 14:23:29'),
(3, 'max_users', '1000', 'Maximum registered users', '2025-12-24 15:38:04', '2025-12-24 15:38:04'),
(4, 'server_ip', 'nl-2.nura.host', 'Minecraft server IP', '2025-12-24 15:38:04', '2025-12-24 19:37:59'),
(5, 'server_version', '1.21.4+', 'Minecraft version', '2025-12-24 15:38:04', '2025-12-24 15:38:04'),
(21, 'server_port', '25586', 'Minecraft server port', '2025-12-24 15:47:24', '2025-12-24 15:47:24'),
(22, 'server_status', 'online', 'Server status', '2025-12-24 15:47:24', '2025-12-24 15:47:24'),
(23, 'max_players', '50', 'Maximum online players', '2025-12-24 15:47:24', '2025-12-24 15:47:24'),
(24, 'server_world', 'aChill Survival', 'Main world name', '2025-12-24 15:47:24', '2025-12-24 15:54:24'),
(25, 'gamemode', 'survival', 'Default game mode', '2025-12-24 15:47:24', '2025-12-24 15:47:24'),
(26, 'difficulty', 'normal', 'Game difficulty', '2025-12-24 15:47:24', '2025-12-24 15:47:24'),
(27, 'allow_pvp', '1', 'Allow PvP combat', '2025-12-24 15:47:24', '2025-12-24 15:47:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `created_at` datetime DEFAULT current_timestamp(),
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `2fa_enabled` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `google_id`, `avatar`, `role`, `created_at`, `full_name`, `phone`, `status`, `password_changed_at`, `last_activity`, `updated_at`, `2fa_enabled`, `last_login`, `is_active`, `is_verified`, `email_verified_at`, `reset_token`, `reset_expires`, `otp_code`, `otp_expires`) VALUES
(1, 'admin', 'admin@test.com', '$2y$10$bZ/PQLeJAGYVYtB5suFTae5ZOU2YLn37vfBfa5Isc2d6VF8DUoH0S', NULL, NULL, 'admin', '2025-12-20 20:35:56', NULL, NULL, 'active', NULL, NULL, '2026-06-15 11:16:07', 0, NULL, 0, 1, NULL, NULL, NULL, NULL, NULL),
(2, 'player', 'player@test.com', '$2y$10$ut2JICEShkPl6T.v.tDu4eAZPkoFx.uPGBR/t26IEPvWwoSScsflK', NULL, NULL, 'member', '2025-12-20 20:35:56', NULL, NULL, 'active', NULL, NULL, '2026-06-15 11:16:07', 0, NULL, 0, 1, NULL, NULL, NULL, NULL, NULL),
(3, 'Rasyad123', 'azzarthfatly@gmail.com', '$2y$10$RFrumJf9rGo0TuP9JU3C3ewOohaYb3LhYVm0ZcJ.FvrdVqZRT7aVi', '117674628260896399647', NULL, 'admin', '2025-12-20 20:36:52', 'Rasyad Fylith Orzylr', '083899744602', 'active', '2026-06-10 11:22:42', NULL, '2026-06-16 01:42:59', 0, '2026-06-16 01:42:59', 1, 1, NULL, NULL, NULL, '759399', '2026-06-13 04:53:07'),
(4, 'Xenonite', 'xxenonitee@gmail.com', '$2y$10$8ip22PxyiPfKPaI5nnXOkOuubKvmAlxb3PJfHaGahuRVvd5DKsgAW', NULL, NULL, 'member', '2025-12-20 22:34:12', 'Ikhwan Manshur', '081510050145', 'active', '2025-12-24 13:44:00', NULL, '2026-06-15 11:12:27', 0, '2025-12-26 14:46:54', 1, 1, NULL, NULL, NULL, NULL, NULL),
(7, 'add', 'add@testing.com', '$2y$10$GV4QsPP8ZSwIHtfZ.64dLOF2ZjILgoTdJCFBx/ts6dPwL.q4/s1bi', NULL, NULL, 'member', '2025-12-24 22:24:11', 'add', NULL, 'active', NULL, NULL, '2026-06-10 14:21:37', 0, '2026-04-25 06:57:18', 0, 1, NULL, NULL, NULL, NULL, NULL),
(8, 's4', 's4@chillcom.com', '$2y$10$2hCzR2wuDVDVWvtK/JhBlO.xfFTxxzuzd.3sEo6d7Eu18wUHizlJW', NULL, NULL, 'member', '2026-06-10 18:02:05', NULL, NULL, 'active', NULL, NULL, '2026-06-10 14:21:37', 0, NULL, 0, 0, NULL, NULL, NULL, '834148', '2026-06-10 18:12:05'),
(14, 'achachabi', 'achaslsbillahh@gmail.com', '$2y$10$A6oqcPfneUKqm2F91Iqxv.aK7yzOJrbvMzo8I8gJ1EdIy0uiLpf2C', NULL, NULL, 'admin', '2026-06-10 18:51:21', 'Acha Salsabila', '081558247261', 'active', '2026-06-10 12:00:05', NULL, '2026-06-10 14:12:44', 0, '2026-06-10 14:12:44', 1, 0, NULL, NULL, NULL, NULL, NULL),
(16, 'Zieless', 'rayhziefylith@gmail.com', NULL, '113229346292415373064', NULL, 'member', '2026-06-15 18:01:45', 'Rayhze', '085817815589', 'active', NULL, NULL, '2026-06-15 11:17:18', 0, '2026-06-15 11:16:58', 1, 0, NULL, NULL, NULL, NULL, NULL),
(17, 'markanalfathikanz', 'markanalfathikanz@gmail.com', NULL, '107333845815096798765', NULL, 'admin', '2026-06-15 18:22:28', '', NULL, 'active', NULL, NULL, '2026-06-15 11:24:45', 0, '2026-06-15 11:24:45', 1, 0, NULL, NULL, NULL, NULL, NULL),
(18, 'dwicandraramadhania930', 'dwicandraramadhania930@gmail.com', NULL, '103516177198704883510', NULL, 'member', '2026-06-15 18:23:59', NULL, NULL, 'active', NULL, NULL, '2026-06-15 11:23:59', 0, '2026-06-15 11:23:59', 1, 0, NULL, NULL, NULL, NULL, NULL),
(19, 'rizkymahendra690', 'rizkymahendra690@gmail.com', NULL, '101655532403563650569', NULL, 'member', '2026-06-15 18:25:51', NULL, NULL, 'active', NULL, NULL, '2026-06-15 11:25:51', 0, '2026-06-15 11:25:51', 1, 0, NULL, NULL, NULL, NULL, NULL),
(20, 'naufalasadelfathi', 'naufalasadelfathi@gmail.com', NULL, '101413557504946939919', NULL, 'member', '2026-06-15 09:47:45', NULL, NULL, 'active', NULL, NULL, '2026-06-15 16:47:45', 0, '2026-06-15 16:47:45', 1, 0, NULL, NULL, NULL, NULL, NULL),
(21, 'shelliani8766', 'shelliani8766@gmail.com', NULL, '101389319163185073378', NULL, 'member', '2026-06-15 18:41:25', NULL, NULL, 'active', NULL, NULL, '2026-06-16 01:41:25', 0, '2026-06-16 01:41:25', 1, 0, NULL, NULL, NULL, NULL, NULL),
(22, 'zahranalpharzq', 'zahranalpharzq@gmail.com', NULL, '101107210925153035758', NULL, 'member', '2026-06-15 18:42:06', NULL, NULL, 'active', NULL, NULL, '2026-06-16 01:42:06', 0, '2026-06-16 01:42:06', 1, 0, NULL, NULL, NULL, NULL, NULL),
(23, 'dailangibran747', 'dailangibran747@gmail.com', '$2y$10$Fk4/GHC44nXbRK9Giy4TfOm1vF4Bh5QOHGXmewP0ka6w503ij.CP2', NULL, NULL, 'member', '2026-06-15 18:44:45', '', NULL, 'active', NULL, NULL, '2026-06-16 01:44:45', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(24, 'faizfaqot23', 'faizfaqot23@gmail.com', '$2y$10$zFLdBC.s.BJeNOUP8LiCK.wthY2QpDCJ9YohglhgRZNqVmqwzf4ZS', NULL, NULL, 'member', '2026-06-15 18:45:15', '', NULL, 'active', NULL, NULL, '2026-06-16 01:45:15', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(25, 'torimansyur46327', 'torimansyur46327@gmail.com', '$2y$10$8xHd5tpe4lwcm70qbkVY/OK.aLczg4zc9IopwidsAip14QMGgWq9.', NULL, NULL, 'member', '2026-06-15 18:46:05', '', NULL, 'active', NULL, NULL, '2026-06-16 01:46:05', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(26, 'snowcoldeza', 'snowcoldeza@gmail.com', '$2y$10$KfRWn6CD3vwa4KXVBLpde.HJWhYL.PB9j0LAElPeUxuj7p/3Svi/S', NULL, NULL, 'member', '2026-06-15 18:47:04', '', NULL, 'active', NULL, NULL, '2026-06-16 01:47:04', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(27, 'skullridereza', 'skullridereza@gmail.com', '$2y$10$SgrJUECIXgmK6SYxH8lsv.23KrTwx3Idd7B9gQK9phJOEuZTC52me', NULL, NULL, 'member', '2026-06-15 18:47:24', '', NULL, 'active', NULL, NULL, '2026-06-16 01:47:24', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(28, 'alviantotken', 'alviantotken@gmail.com', '$2y$10$eDVcaeIM9nzn1WCH8Io5IuaGYiH76AS3F3.zb6ITOlDxf.JJAK3cG', NULL, NULL, 'member', '2026-06-15 18:47:56', '', NULL, 'active', NULL, NULL, '2026-06-16 01:47:56', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(29, 'airinenursyifa', 'airinenursyifa@gmail.com', '$2y$10$4Z6lSoG4ZfwENs68p./jgO6W/4eErFEbRrltbB21l/VPeM3ZIZ8Se', NULL, NULL, 'member', '2026-06-15 18:49:43', '', NULL, 'active', NULL, NULL, '2026-06-16 01:49:43', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(30, 'kenzyxiaulynx', 'kenzyxiaulynx@gmail.com', '$2y$10$/.Yeryz84Hsdvpb5oUCPs.kblog/LT9rnF5GnEMMTZypqATHVw6z.', NULL, NULL, 'member', '2026-06-15 18:50:01', '', NULL, 'active', NULL, NULL, '2026-06-16 01:50:01', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(31, 'zazaanasafira', 'zazaanasafira@gmail.com', '$2y$10$OQyUSFnJQGNZPBNEbkOJzOXt5.MbvqFYzoFYqI2ZBgD0/NCLbqFGW', NULL, NULL, 'member', '2026-06-15 18:50:24', '', NULL, 'active', NULL, NULL, '2026-06-16 01:50:24', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(32, 'farelraynandra762', 'farelraynandra762@gmail.com', '$2y$10$xVBGQyXgOiUuoqgurxGDfuN3iC/Vsbj0mNXHs5mFw6/evkzKvFOeq', NULL, NULL, 'member', '2026-06-15 18:50:50', '', NULL, 'active', NULL, NULL, '2026-06-16 01:50:50', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(33, 'evanalverio0', 'evanalverio0@gmail.com', '$2y$10$Fki99zwxx2axlW9MwZVR3.CJpEVh/y9YCDmxg52zhmXi8I.XgUd8i', NULL, NULL, 'member', '2026-06-15 18:51:14', '', NULL, 'active', NULL, NULL, '2026-06-16 01:51:14', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(34, 'barlanfatinnurhermansyah', 'barlanfatinnurhermansyah@gmail.com', '$2y$10$mK96OAlBRzgiu6ysUCppdeU/y1OwmDShxAogOOtNPzlfhYL2D406e', NULL, NULL, 'member', '2026-06-15 18:52:20', '', NULL, 'active', NULL, NULL, '2026-06-16 01:52:20', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(35, 'nuryayathayati82', 'nuryayathayati82@gmail.com', '$2y$10$A3m.jS6D2h7z/TYhfwVepOik5EAyn7278dPML.3dr0hUA0Q8UnrvO', NULL, NULL, 'member', '2026-06-15 18:52:49', '', NULL, 'active', NULL, NULL, '2026-06-16 01:52:49', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(36, 'hananf1129', 'hananf1129@gmail.com', '$2y$10$YuPI/g2r9/cLifs06XlVee81s6iTryL86WNh/BFBgzyzXxIr0i8a6', NULL, NULL, 'member', '2026-06-15 18:53:08', '', NULL, 'active', NULL, NULL, '2026-06-16 01:53:08', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(37, 'farhanhabibbinsyahrul', 'farhanhabibbinsyahrul@gmail.com', '$2y$10$4ld1jGkpvI9JTfo/tp5FtuE3ivIad/NzoI66G0N.VjxP3N601p.AW', NULL, NULL, 'member', '2026-06-15 18:53:36', '', NULL, 'active', NULL, NULL, '2026-06-16 01:53:36', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(38, 'vyrlothex', 'vyrlothex@gmail.com', NULL, '115361905585972364123', NULL, 'member', '2026-06-15 19:05:48', NULL, NULL, 'active', NULL, NULL, '2026-06-16 02:05:48', 0, '2026-06-16 02:05:48', 1, 0, NULL, NULL, NULL, NULL, NULL),
(39, 'jutkanyutafk', 'jut.kanyutafk@gmail.com', NULL, '117960087675778216394', NULL, 'member', '2026-06-15 19:06:34', NULL, NULL, 'active', NULL, NULL, '2026-06-16 02:06:34', 0, '2026-06-16 02:06:34', 1, 0, NULL, NULL, NULL, NULL, NULL),
(40, 'playerxxx21116', 'playerxxx21116@gmail.com', NULL, '109269856716935016381', NULL, 'member', '2026-06-15 19:07:07', NULL, NULL, 'active', NULL, NULL, '2026-06-16 02:07:07', 0, '2026-06-16 02:07:07', 1, 0, NULL, NULL, NULL, NULL, NULL),
(41, 'shinzhaolynx', 'shinzhaolynx@gmail.com', NULL, '107686231026828813668', NULL, 'member', '2026-06-15 19:07:16', NULL, NULL, 'active', NULL, NULL, '2026-06-16 02:07:16', 0, '2026-06-16 02:07:16', 1, 0, NULL, NULL, NULL, NULL, NULL),
(42, 'xiittarrr', 'xiittarrr@gmail.com', '$2y$10$H5w3U523pua8XZ/meesSD.G01BnkFWbYBLvd52jfwRfO966yowKUi', NULL, NULL, 'admin', '2026-06-15 19:13:13', '', NULL, 'active', NULL, NULL, '2026-06-16 02:13:13', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(43, 'Muafa', 'darkdestroyer961@gmail.com', '$2y$10$0qr0EyBIP/LVh9supvbZWeTnzeVnktRnE2ARwcBY5fcm2RgY9o6wW', NULL, NULL, 'admin', '2026-06-15 19:13:54', '', NULL, 'active', NULL, NULL, '2026-06-16 02:13:54', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(44, 'deniandre3361', 'deniandre3361@gmail.com', '$2y$10$zYFhBDj.1RBleh9JkeDOXe7kmqsTUiZ9jrUynTlfciDl5UhxZhlqi', NULL, NULL, 'member', '2026-06-15 19:14:25', '', NULL, 'active', NULL, NULL, '2026-06-16 02:14:25', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL),
(45, 'hanif1e', 'hanif1e@gmail.com', '$2y$10$W/ZndL/k/JQRpxHVY7FjWO1V6VkeA5UHCWO9BR3g5vM7pvC7wl0zC', NULL, NULL, 'admin', '2026-06-15 19:15:12', '', NULL, 'active', NULL, NULL, '2026-06-16 02:15:12', 0, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deleted_accounts`
--
ALTER TABLE `deleted_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_event_type` (`event_type`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`user_id`,`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `export_logs`
--
ALTER TABLE `export_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_export_time` (`export_time`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_time` (`login_time`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `ranks`
--
ALTER TABLE `ranks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rank_features`
--
ALTER TABLE `rank_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rank_id` (`rank_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deleted_accounts`
--
ALTER TABLE `deleted_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `export_logs`
--
ALTER TABLE `export_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ranks`
--
ALTER TABLE `ranks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rank_features`
--
ALTER TABLE `rank_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
