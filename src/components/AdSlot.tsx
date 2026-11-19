"use client";
import { useEffect, useRef } from "react";

declare global {
  interface Window { adsbygoogle?: unknown[] }
}

/** Manuel reklam alanı. Slot ID yoksa (yalnızca Otomatik Reklamlar kullanılıyorsa) hiçbir şey göstermez. */
export default function AdSlot({ client, slot, format = "auto", className = "" }: { client?: string; slot?: string; format?: string; className?: string }) {
  const ref = useRef<HTMLModElement>(null);
  useEffect(() => {
    if (!client || !slot || !ref.current || ref.current.dataset.loaded) return;
    ref.current.dataset.loaded = "1";
    try {
      (window.adsbygoogle = window.adsbygoogle || []).push({});
    } catch {}
  }, [client, slot]);
  if (!client || !slot) return null;
  return (
    <div className={`ad ${className}`}>
      <span className="ad-label">Reklam</span>
      <ins ref={ref} className="adsbygoogle" style={{ display: "block" }} data-ad-client={client} data-ad-slot={slot} data-ad-format={format} data-full-width-responsive="true" />
    </div>
  );
}
