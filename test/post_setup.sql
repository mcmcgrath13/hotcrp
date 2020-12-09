UPDATE Paper SET leadContactId = paperId+1, shepherdContactId = paperId+2, managerContactId = paperId+3 WHERE paperId < 20;

UPDATE Capability SET timeExpires = UNIX_TIMESTAMP() + contactId * 90;
