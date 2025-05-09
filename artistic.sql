-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2025 at 04:38 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `artistic`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('admin','artist','enthusiast','both') NOT NULL DEFAULT 'enthusiast',
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_super_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `profile_picture`, `bio`, `role`, `created_at`, `last_login`, `is_active`, `is_super_admin`) VALUES
(4, 'jana', 'jana@gmail.com', '$2y$10$UfdDSymvymbEA6kDYE.W2OU4sAsMGrZoZ0frzETAPDn0xeiVLhZNa', NULL, NULL, 'enthusiast', '2025-04-28 23:38:54', NULL, 1, 0),
(5, 'batool', 'batool@gmail.com', '$2y$10$K0OGQW03bwhQ8qqVZw9oqOti8Dc/uRIGC5qDHqji45ZodIygTYXoq', NULL, NULL, 'enthusiast', '2025-04-28 23:41:08', NULL, 1, 0),
(7, 'janas', 'janav@gmail.com', '$2y$10$g52HRuDy.VelUG4RgLa1BOEu8JLarnlWfZP8LmGOandBzOttGnLGm', NULL, NULL, 'enthusiast', '2025-04-28 23:43:10', NULL, 1, 0),
(8, 'roaa', 'roaa@gmail.com', '$2y$10$TNs25XreaRRNTyZeFYlPL.DZKB1FHx3o.1atYDAz2jGivQFJtOjpK', NULL, NULL, 'enthusiast', '2025-04-28 23:45:23', NULL, 1, 0),
(10, 'jana4', 'jana@yahoo.com', '$2y$10$Z2Pq/Xop/tkeRt7rrqrvDuAmDH9IUthGd9jq6YmZcjQNu6PKv4Q1i', NULL, NULL, 'enthusiast', '2025-04-28 23:46:39', NULL, 1, 0),
(11, 'mohammad', 'mohammad@gmail.com', '$2y$10$UGjWJi355I/WJkquMSPQqedGdYPFuqAt7bIu/r14FicTTRBGpa.Nq', NULL, NULL, 'enthusiast', '2025-04-29 15:57:51', NULL, 1, 0),
(12, 'asma', 'asma@gmail.com', '$2y$10$d0YwsVlTaa.vlvopHQypCuFdEvYs7NcxbdcZeSn5J/kOZt6PLP88m', NULL, NULL, 'enthusiast', '2025-04-29 16:12:14', NULL, 1, 0),
(13, 'asoom', 'asoom@gmail.com', '$2y$10$BdwcNNfyQv33iVI4Vulf3.2oW1yKovCtZbRBMcsnWoNVQJmNKLRuC', NULL, NULL, 'artist', '2025-04-29 16:14:56', NULL, 1, 0),
(14, 'asoooom', 'asoooom@gmail.com', '$2y$10$6df8xmMIT5Wgn8IsQM4wyOXeyHX1lCLtlAOczlDJ8GAHH5xRnm5Ca', NULL, NULL, 'artist', '2025-04-29 16:20:26', NULL, 1, 0),
(15, 'nada', 'nada@gmail.com', '$2y$10$Sdga69R/Ng5F1zx60q.S7ON9sWwsm8wQz4lhU7Jur4BsRzYOAIjXS', NULL, NULL, 'both', '2025-04-29 16:45:17', NULL, 1, 0),
(16, 'jawad', 'jawad@gmail.com', '$2y$10$57mrp5EX0SJuPsKPz/2G1udGJzj4G8oRepqnxsxJiH982pmhj7hEy', NULL, NULL, 'both', '2025-04-29 17:02:36', NULL, 1, 0),
(18, 'janayt', 'janaiuiu@gmail.com', '$2y$10$rlmdOE9RsHoLjOItyURqzOLd54.2IOOAWVQvoaJbTj37GgJQQQEz2', NULL, NULL, 'enthusiast', '2025-04-29 17:17:11', NULL, 1, 0),
(19, 'maram', 'maram@gmail.com', '$2y$10$FnK40PqzDJtumvsDO524x.0NkvnA8px68wsSx1DudGUZYyzZ3MzPu', NULL, NULL, 'enthusiast', '2025-04-29 17:19:37', NULL, 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
