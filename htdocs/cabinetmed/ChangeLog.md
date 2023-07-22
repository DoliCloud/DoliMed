# ChangeLog

## Unreleased

- NEW Add more substitution for dates

## 10.0.0

- NEW Add entity column on consultations

## 9.0.3

- NEW Compatibility with v15. No more compatible with v11.
- FIX Box of last patient was output on wrong page.

## 9.0.2

- Fix call to undefined dol_cm_strptime()
- Fix filters on list of patients, consultations and contacts

## 9.0.1

- Compatibility of export with PHP 7.4
- Can set SOCIETE_DISABLE_PARENTCOMPANY to enable use of parent company.
- Add extrafields of consultation into export of patient and consultations.
- Show hours on date of last consultation.
- Add option CABINETMED_ACCOUNTANCY_CODE_FOR_CONSULTATION (need Dolibarr v13)
- Patient record on 2 columns
- Need Dolibarr v11+

## 9.0.0

- NEW Compatibility with v9, v10, v11.
- NEW Add preview on files.
- NEW Add outcome_comment on document.
- NEW Support extrafields on list of consultations.
- NEW Add option DIAGNOSTIC_IS_NOT_MANDATORY.
- NEW Add tags for extrafields in ODT templates {patient_options_xxx} and {outcome_options_xxx}.
- FIX phone and zip.
- FIX Do not loose name of banque if the bank was renamed with a different case.

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
