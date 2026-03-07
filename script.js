let data = [
 {name:"ตรายาง A",price:""},
 {name:"ตรายาง B",price:""}
]

if(localStorage.getItem("products")){
 data = JSON.parse(localStorage.getItem("products"))
}

function load(){

 let html = ""

 data.forEach((p,i)=>{

  html += `
  <tr>
   <td>${p.name}</td>
   <td>
   <input type="number" value="${p.price}" id="price${i}">
   </td>
  </tr>
  `
 })

 document.getElementById("table").innerHTML = html

}

function save(){

 data.forEach((p,i)=>{
  p.price = document.getElementById("price"+i).value
 })

 localStorage.setItem("products",JSON.stringify(data))

 alert("บันทึกแล้ว")

}

load()