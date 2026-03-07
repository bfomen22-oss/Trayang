"use client"
import { useState, useEffect } from 'react'
import { createClient } from '@supabase/supabase-js'

// เชื่อมต่อ Supabase (เอา URL และ Anon Key มาจาก Dashboard ของคุณ)
const supabase = createClient('https://plpaoldrfhzbktyxfuwl.supabase.co', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBscGFvbGRyZmh6Ymt0eXhmdXdsIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzI4NDE1MTksImV4cCI6MjA4ODQxNzUxOX0.5eA3bX9pE4fmC1LcSsRqbZca4RDfScwwe7SoQSoOrIg')

export default function MyWeb() {
  const [post, setPost] = useState(null)
  const [isEditing, setIsEditing] = useState(false)
  const [loading, setLoading] = useState(true)

  // 1. ดึงข้อมูลจาก Database
  useEffect(() => {
    fetchPost()
  }, [])

  async function fetchPost() {
    const { data } = await supabase.from('posts').select('*').single()
    if (data) setPost(data)
    setLoading(false)
  }

  // 2. ฟังก์ชันอัปเดตข้อมูล
  async function handleSave() {
    const { error } = await supabase
      .from('posts')
      .update({ title: post.title, content: post.content })
      .eq('id', post.id)
    
    if (!error) {
      setIsEditing(false)
      alert("บันทึกสำเร็จ!")
    }
  }

  // 3. ฟังก์ชันอัปโหลดรูป (Storage)
  async function uploadImage(e) {
    const file = e.target.files[0]
    const fileExt = file.name.split('.').pop()
    const fileName = `${Math.random()}.${fileExt}`
    
    let { error: uploadError } = await supabase.storage
      .from('images') // ต้องไปสร้าง Bucket ชื่อ images ใน Supabase ก่อน
      .upload(fileName, file)

    if (!uploadError) {
      const { data } = supabase.storage.from('images').getPublicUrl(fileName)
      setPost({ ...post, image_url: data.publicUrl })
      // อัปเดต URL รูปใน Database ด้วย
      await supabase.from('posts').update({ image_url: data.publicUrl }).eq('id', post.id)
    }
  }

  if (loading) return <div className="p-10 text-center">กำลังโหลด...</div>

  return (
    <main className="max-w-2xl mx-auto p-6 bg-white shadow-lg mt-10 rounded-xl">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">My Personal Page</h1>
        <button 
          onClick={() => isEditing ? handleSave() : setIsEditing(true)}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          {isEditing ? '💾 บันทึกข้อมูล' : '⚙️ แก้ไข'}
        </button>
      </div>

      <div className="space-y-4">
        {/* ส่วนของรูปภาพ */}
        <div className="relative">
          <img src={post.image_url} alt="Cover" className="w-full h-64 object-cover rounded-lg" />
          {isEditing && (
            <input type="file" onChange={uploadImage} className="mt-2 text-sm" />
          )}
        </div>

        {/* ส่วนของข้อความ */}
        {isEditing ? (
          <div className="flex flex-col gap-2">
            <input 
              className="border p-2 rounded text-xl font-bold"
              value={post.title} 
              onChange={(e) => setPost({...post, title: e.target.value})}
            />
            <textarea 
              className="border p-2 rounded h-32"
              value={post.content} 
              onChange={(e) => setPost({...post, content: e.target.value})}
            />
          </div>
        ) : (
          <div>
            <h2 className="text-3xl font-bold">{post.title}</h2>
            <p className="mt-4 text-gray-600 leading-relaxed whitespace-pre-wrap">{post.content}</p>
          </div>
        )}
      </div>
    </main>
  )
}