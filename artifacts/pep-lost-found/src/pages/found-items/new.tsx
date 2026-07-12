import React, { useState } from "react";
import { useLocation } from "wouter";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Camera, Image as ImageIcon, Loader2, ArrowLeft } from "lucide-react";
import { useCreateFoundItem, FoundItemInputCategory } from "@workspace/api-client-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage, FormDescription } from "@/components/ui/form";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { useToast } from "@/hooks/use-toast";

const formSchema = z.object({
  category: z.enum([
    "財布・カバン類", "衣類", "電子機器", "傘", "その他"
  ] as const),
  subCategory: z.string().optional(),
  features: z.string().min(1, "特徴を入力してください"),
  foundDatetime: z.string().min(1, "拾得日時を入力してください"),
  foundLocation: z.string().optional(),
  storageLocation: z.string().optional(),
  imageUrl: z.string().optional(),
  finderInfo: z.object({
    name: z.string().optional(),
    contact: z.string().optional(),
    rightsWaived: z.boolean().default(false),
  }).optional()
});

type FormValues = z.infer<typeof formSchema>;

export default function NewFoundItem() {
  const [, setLocation] = useLocation();
  const { toast } = useToast();
  const createItem = useCreateFoundItem();
  const [uploading, setUploading] = useState(false);
  const [imagePreview, setImagePreview] = useState<string | null>(null);

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      category: "財布・カバン類",
      subCategory: "",
      features: "",
      foundDatetime: new Date().toISOString().slice(0, 16), // YYYY-MM-DDThh:mm
      foundLocation: "",
      storageLocation: "",
      imageUrl: "",
      finderInfo: {
        name: "",
        contact: "",
        rightsWaived: false
      }
    }
  });

  const handleImageUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Show local preview immediately
    const reader = new FileReader();
    reader.onload = (e) => {
      setImagePreview(e.target?.result as string);
    };
    reader.readAsDataURL(file);

    setUploading(true);
    const formData = new FormData();
    formData.append("image", file);

    try {
      const response = await fetch("/api/upload/image", {
        method: "POST",
        body: formData,
      });

      if (!response.ok) throw new Error("アップロードに失敗しました");
      
      const data = await response.json();
      form.setValue("imageUrl", data.url);
      toast({ title: "画像をアップロードしました" });
    } catch (error) {
      toast({ 
        title: "エラー", 
        description: "画像のアップロードに失敗しました", 
        variant: "destructive" 
      });
      setImagePreview(null);
    } finally {
      setUploading(false);
    }
  };

  const onSubmit = async (values: FormValues) => {
    try {
      const result = await createItem.mutateAsync({
        data: {
          category: values.category,
          subCategory: values.subCategory || undefined,
          features: values.features,
          foundDatetime: new Date(values.foundDatetime).toISOString(),
          foundLocation: values.foundLocation || undefined,
          storageLocation: values.storageLocation || undefined,
          imageUrl: values.imageUrl || undefined,
          finderInfo: {
            name: values.finderInfo?.name || null,
            contact: values.finderInfo?.contact || null,
            rightsWaived: values.finderInfo?.rightsWaived || false,
          }
        }
      });
      
      toast({ title: "拾得物を登録しました", description: `管理番号: ${result.managementNo}` });
      setLocation(`/found-items/${result.id}`);
    } catch (error) {
      toast({ title: "登録に失敗しました", variant: "destructive" });
    }
  };

  return (
    <div className="max-w-2xl mx-auto space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => setLocation("/found-items")}>
          <ArrowLeft className="w-5 h-5" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">新規拾得物登録</h1>
          <p className="text-sm text-muted-foreground">新しい落とし物の情報をシステムに登録します。</p>
        </div>
      </div>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8">
          <Card className="shadow-sm border-border/50">
            <CardContent className="p-6 space-y-6">
              
              {/* Image Upload Section */}
              <div className="space-y-3">
                <label className="text-sm font-medium leading-none">写真</label>
                <div className="flex flex-col items-center justify-center border-2 border-dashed border-border/60 rounded-xl bg-secondary/20 p-6 relative overflow-hidden transition-colors hover:bg-secondary/40">
                  {imagePreview ? (
                    <img src={imagePreview} alt="プレビュー" className="w-full max-w-[300px] h-auto rounded-md object-contain z-10" />
                  ) : (
                    <div className="flex flex-col items-center gap-2 text-muted-foreground z-10">
                      <div className="w-12 h-12 rounded-full bg-secondary flex items-center justify-center">
                        <Camera className="w-6 h-6" />
                      </div>
                      <p className="text-sm font-medium">タップして写真を撮影または選択</p>
                    </div>
                  )}
                  {uploading && (
                    <div className="absolute inset-0 bg-background/80 backdrop-blur-sm z-20 flex items-center justify-center">
                      <Loader2 className="w-8 h-8 animate-spin text-primary" />
                    </div>
                  )}
                  <input 
                    type="file" 
                    accept="image/*" 
                    className="absolute inset-0 opacity-0 cursor-pointer z-30" 
                    onChange={handleImageUpload}
                    disabled={uploading}
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormField
                  control={form.control}
                  name="category"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>カテゴリ <span className="text-destructive">*</span></FormLabel>
                      <Select onValueChange={field.onChange} defaultValue={field.value}>
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="カテゴリを選択" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {Object.values(FoundItemInputCategory).map(cat => (
                            <SelectItem key={cat} value={cat}>{cat}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="subCategory"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>サブカテゴリ</FormLabel>
                      <FormControl>
                        <Input placeholder="例: 黒い長財布" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <FormField
                control={form.control}
                name="features"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>特徴 <span className="text-destructive">*</span></FormLabel>
                    <FormControl>
                      <Textarea placeholder="色、ブランド、中身の特徴など" className="resize-none min-h-[100px]" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormField
                  control={form.control}
                  name="foundDatetime"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>拾得日時 <span className="text-destructive">*</span></FormLabel>
                      <FormControl>
                        <Input type="datetime-local" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="foundLocation"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>拾得場所</FormLabel>
                      <FormControl>
                        <Input placeholder="例: 南口ゲート付近ベンチ" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <FormField
                control={form.control}
                name="storageLocation"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>保管場所</FormLabel>
                    <FormControl>
                      <Input placeholder="例: 管理センター 棚A-3" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </CardContent>
          </Card>

          <Card className="shadow-sm border-border/50">
            <CardContent className="p-6 space-y-6">
              <h3 className="font-bold border-b pb-2">拾得者情報 (任意)</h3>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormField
                  control={form.control}
                  name="finderInfo.name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>氏名</FormLabel>
                      <FormControl>
                        <Input placeholder="山田 太郎" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="finderInfo.contact"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>連絡先</FormLabel>
                      <FormControl>
                        <Input placeholder="090-XXXX-XXXX" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <FormField
                control={form.control}
                name="finderInfo.rightsWaived"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4 bg-secondary/10">
                    <FormControl>
                      <Checkbox
                        checked={field.value}
                        onCheckedChange={field.onChange}
                      />
                    </FormControl>
                    <div className="space-y-1 leading-none">
                      <FormLabel>
                        拾得者の権利（報労金等）を放棄する
                      </FormLabel>
                      <FormDescription>
                        拾得者がお礼や所有権を辞退した場合はチェックを入れてください。
                      </FormDescription>
                    </div>
                  </FormItem>
                )}
              />
            </CardContent>
          </Card>

          <div className="flex justify-end gap-4">
            <Button type="button" variant="outline" onClick={() => setLocation("/found-items")}>
              キャンセル
            </Button>
            <Button type="submit" disabled={createItem.isPending || uploading} className="px-8 shadow-sm">
              {createItem.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              登録する
            </Button>
          </div>
        </form>
      </Form>
    </div>
  );
}