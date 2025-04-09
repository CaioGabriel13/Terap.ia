CREATE TABLE `appointments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `psychologist_id` INT(11) NOT NULL,
  `patient_id` INT(11) NOT NULL,
  `appointment_date` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`psychologist_id`) REFERENCES users(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES users(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
