function scDisplayUserError(errorMessage) {
	alert("ERROR\r\n" + errorMessage.replace(/<br \/>/gi, "\n"));
}
function scDisplayUserDebug(debugMessage) {
	alert("DEBUG\r\n" + debugMessage.replace(/<br \/>/gi, "\n"));
}function scDisplayUserMessage(userMessage) {
	alert("MESSAGE\r\n" + userMessage.replace(/<br \/>/gi, "\n"));
}