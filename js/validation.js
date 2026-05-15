export function validation(name, email, studentClass){
    if(name === "" || email === "" || studentClass === ""){
         alert("please fill all fields!");
    return false ;
    }
 return true ;
}