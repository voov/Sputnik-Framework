SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `acl_groups`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_groups` ;

CREATE  TABLE IF NOT EXISTS `acl_groups` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `parent_id` INT NOT NULL DEFAULT 0 ,
  `name` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_acl_groups_acl_groups1`
    FOREIGN KEY (`parent_id` , `id` )
    REFERENCES `acl_groups` (`id` , `parent_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = MyISAM;

CREATE INDEX `fk_acl_groups_acl_groups1` ON `acl_groups` (`parent_id` ASC, `id` ASC) ;


-- -----------------------------------------------------
-- Table `acl_resources`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_resources` ;

CREATE  TABLE IF NOT EXISTS `acl_resources` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `parent_id` INT NOT NULL DEFAULT 0 ,
  `name` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_acl_resources_acl_resources1`
    FOREIGN KEY (`parent_id` , `id` )
    REFERENCES `acl_resources` (`id` , `parent_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = MyISAM;

CREATE INDEX `fk_acl_resources_acl_resources1` ON `acl_resources` (`parent_id` ASC, `id` ASC) ;

CREATE INDEX `idx_name` ON `acl_resources` (`name` ASC) ;


-- -----------------------------------------------------
-- Table `acl_actions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_actions` ;

CREATE  TABLE IF NOT EXISTS `acl_actions` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `resource_id` INT NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_acl_actions_acl_resources`
    FOREIGN KEY (`resource_id` )
    REFERENCES `acl_resources` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = MyISAM;

CREATE INDEX `fk_acl_actions_acl_resources` ON `acl_actions` (`resource_id` ASC) ;

CREATE INDEX `idx_name` ON `acl_actions` (`name` ASC) ;


-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users` ;

CREATE  TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `username` VARCHAR(45) NOT NULL ,
  `password` VARCHAR(32) NOT NULL ,
  `group_id` INT NOT NULL DEFAULT 0 ,
  `active` SET('0', '1') NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_users_acl_groups1`
    FOREIGN KEY (`group_id` )
    REFERENCES `acl_groups` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = MyISAM;

CREATE INDEX `fk_users_acl_groups1` ON `users` (`group_id` ASC) ;


-- -----------------------------------------------------
-- Table `acl_permissions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `acl_permissions` ;

CREATE  TABLE IF NOT EXISTS `acl_permissions` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `member_id` INT NOT NULL COMMENT 'either user or group ID' ,
  `action_id` INT NOT NULL ,
  `type` INT NOT NULL ,
  `enabled` SET('0', '1') NOT NULL DEFAULT 1 ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_acl_permissions_acl_actions1`
    FOREIGN KEY (`action_id` )
    REFERENCES `acl_actions` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_acl_permissions_users1`
    FOREIGN KEY (`member_id` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_acl_permissions_acl_groups1`
    FOREIGN KEY (`member_id` )
    REFERENCES `acl_groups` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = MyISAM;

CREATE INDEX `fk_acl_permissions_acl_actions1` ON `acl_permissions` (`action_id` ASC) ;

CREATE INDEX `fk_acl_permissions_users1` ON `acl_permissions` (`member_id` ASC) ;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
