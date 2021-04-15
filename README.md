**GUIDE FOR CONNECTING NIOS4 TO MAILCHIMP.**

This guide will show you how to connect Nios4 to Mailchimp via PHP programming language. After all these steps, through the implementation of an action, your management software system Nios4 will be able to automatically interact with your own personal Mailchimp account, in such a way to have the email addresses with their respective tags on Mailchimp, without using manually the system.

The following are all the steps required for the connection to Mailchimp.

1. First at all you need to create a server where you can transfer all the PHP pages.

2. You have to create an account on Mailchimp where you can find your APIkey and the ID of the list where you want to perform your operations.

3. Make the following changes on Nios4:
	- The email must be associated to an unique user, therefore the email field must not allow duplicates and must be required.
	- Add a field on the table where the emails are located. This field must be text type and not editable. Name of the field-> "id_mailchimp". "id_mailchimp" field empty means that the user is not in your Mailchimp account, if it's not empty then the user is on your Mailchimp account.
	- Create a table where all the tags will be saved. This table gives you the possibility to add and remove a tag. This table must not allow duplicates, the fields will be the name of the tag and "id_mailchimp" that will be text type and not editable. "id_mailchimp" field empty means that the tag is not in your Mailchimp account, if it's not empty then the tag is on your Mailchimp account.
	- Create another table. This table may not be visible, it will have a unique field that include a sub-table of the table where the emails will be located.
	- The table where the emails will be located will have an additional table referencing to the table mentioned above.

4. In "PHP_file" folder you can find all the required PHP files. You have to create a copy of each file and save them on your server.

5. In "Script" folder you can find all the necessary scripts you need to create in Nios4. In total, there are 6 scripts necessary for adding a user, creating a tag and their respective removals. You need to copy the code of each script and paste it to your own.

6. Creation of the script for adding a user on Mailchimp:
	- add a script on 'card action' inside the table where the emails are located. Pay attention to insert the right parameters in the appropriate lines indicates with two stars (* _your data_ *). Check the value of the variables on Options->Fields.

7. Creation of the script for removing a user on Mailchimp:
	- add a script on 'post delete card' inside the table where emails are located. Pay attention to insert the right parameters in the appropriate lines indicates with two stars (* _your data_ *). Check the value of the variables on Options->Fields.
	- add a script on 'pre delete table row' inside the table where emails are located. Pay attention to insert the right parameters in the appropriate lines indicates with two stars (* _your data_ *). Check the value of the variables on Options->Fields.

8. Creation of the script for adding a tag on Mailchimp:
	- add a script on 'card action' inside the table where the tags are located. Pay attention to insert the right parameters in the appropriate lines indicates with two stars (* _your data_ *). Check the value of the variables on Options->Fields.

9. Creation of the script for removing a tag on Mailchimp:
	- add a script on 'post delete card' inside the table where the tags are located. Pay attention to insert the right parameters in the appropriate lines indicates with two stars (* _your data_ *). Check the value of the variables on Options->Fields.
	- add a script on 'pre delete table row' inside the table where the tags are located. Pay attention to insert the right parameters in the appropriate lines indicates with two stars (* _your data_ *). Check the value of the variables on Options->Fields.

Limits of Mailchimp's services:

  - When you insert a user to Mailchimp from Nios4, the user status will be SUBSCRIBED.
  - You can change the email address of a user in Mailchimp only if this user is SUBSCRIBED.
  - When you change a tag name, automatically changes all the tags connected to the associated users. It happen only if user status is SUBSCRIBED. For the UNSUBSCRIBED user the tag will be deleted automatically.
  - Mailchimp not allow to modify the email address of an user with an email already present in archives. In this case our advice is to delete him and adding a new user with the right email address.
  - Don't manually change the users status on Mailchimp.
