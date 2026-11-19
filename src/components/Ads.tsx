import AdSlot from "./AdSlot";
import { site } from "@/lib/config";

export function Ad({ position, className }: { position: "top" | "sidebar" | "article"; className?: string }) {
  return <AdSlot client={site.adsenseClient} slot={site.adsenseSlots[position]} className={className} />;
}
