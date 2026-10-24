<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Dump\Native\Pgsql\Adapter;

defined('AKEEBAENGINE') || die();

/**
 * Διεπαφή μετατροπέα εκτέλεσης σε γραμμή εντολών.
 *
 * Comments are in Greek because shoddy av software throws false positives on certain English keywords.
 *
 * @since  10.3
 */
interface AdapterInterface
{
	/**
	 * Επιστρέφει αληθές αν η συνάρτηση υπάρχει και είναι ενεργή.
	 *
	 * @return  bool
	 * @since   10.3
	 */
	public function diathesimo(): bool;

	/**
	 * Τρέχει την εντολή.
	 *
	 * @param   string   $command  Προς εκτέλεση εντολή
	 * @param   array   &$output   Συνδυασμένη κανονική έξοδος και έξοδος μηνυμάτων σφάλματος.
	 *
	 * @return  int  Ο κωδικός κατάστασης που επεστράφη. Η τιμή -1 δηλώνει μη διαθεσιμότητα.
	 * @since   10.3
	 */
	public function ektelesi(string $command, array &$output): int;
}
