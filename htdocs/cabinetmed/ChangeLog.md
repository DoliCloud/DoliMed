# ChangeLog


## 7.0.1

- NEW Compatibilty with Dolibarr v8.
- Fix disable of old contact tab on patient only.
- Fix group by error in stats page of contacts.
- Enable document template when enabling module
- Fix attachment not sent when sending email from documents tab.
- Fix edit of sale representative (following doctor) on patient.
- Dolivarr v6+ required.
- Support extrafields on list of patients.
 
## 7.0.0

- NEW Compatibility with Dolibarr v7
- NEW Can filter on contact on list of patients
- NEW Better menu organization
- NEW Add substitution key outcome_id and patient_id
- External users (care giveers contacts) can have an external account to see patient and consultations by adding option MAIN_DISABLE_RESTRICTION_ON_THIRDPARTY_FOR_EXTERNAL to 1

## 5.0.1

- Better Look and feel v6
- Fix compatibility with MAIN_DB_PREFIX (avoid error on llx_c_type not found)
- Fix translation
- Security fixes
- Fix attachment of document when sending email

## 5.0.0
- Can link elements to consultations.
- Minimum version of Dolibarr is 4.0

## 4.1.0
- Add extrafields on consultations.

## 4.0.0
- Can keep or not feature of thirdparties (invoice, proposal) on patients.
- Use the new Dolibarr 4.0 look.

## 3.7.0
- Work with 3.7
- Add filter on sale representative
- Can send email to patient from patient card.
- Fields Profession, birthdate, weight, heigth are not extrafields. 
- Added nb of notes into badge of tabs.

## 3.6.0
- Add position into databse for combo list.
- CABINETMED_DELAY_TO_LOCK_RECORD option is not visible into setup.

## 3.5.2
- Add hidden option CABINETMED_DELAY_TO_LOCK_RECORD to lock edition of all 
  consultations older than CABINETMED_DELAY_TO_LOCK_RECORD days.
- Add a button to create a consultation from an event linked to a patient.
- Fix: Pb with textarea on small screen. 
- Compatible with Dolibarr 3.5.* and 3.6.*

## 3.5.1
- Minor fixes into translation.
- Prepare compatibility with Dolibarr 3.6.
- Added filter on birthdate into list of patient.
- Fix: Bug when you close an account. Closed account must appears into list.
