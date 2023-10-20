1.Every post controller return data in the formart
{
	message,data,error,success
}
in which success and error  depend on each other
on the other hand if you are making request to deal with user data the return formart is
{message,token and success} only and THIS MUST BE ADTHERED TO AT ALL COST. or else the application frontend wont work as expected ,the token is dependant on the success if the success is true then there is a tocken else there is no tocken so check for success
